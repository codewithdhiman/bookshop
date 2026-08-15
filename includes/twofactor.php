<?php
// includes/twofactor.php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/functions.php';

// If composer libraries are present, prefer them. Otherwise use built-in TOTP implementation.
function tf_has_composer_libs(): bool {
    return class_exists('\OTPHP\TOTP') || class_exists('\Chillerlan\\QRCode\\QRCode') || class_exists('Endroid\\QrCode\\QrCode');
}

// Generate a base32 secret (RFC 3548) length ~16 by default
function tf_generate_secret(int $length = 16): string
{
    $bytes = random_bytes($length);
    return rtrim(base32_encode($bytes), '=');
}

// Create otpauth URI
function tf_provisioning_uri(string $accountName, string $email, string $secret, string $issuer = 'Bookshop'): string
{
    $label = rawurlencode($issuer . ':' . $email);
    $params = http_build_query([ 'secret' => $secret, 'issuer' => $issuer, 'algorithm' => 'SHA1', 'digits' => 6, 'period' => 30 ]);
    return "otpauth://totp/{$label}?{$params}";
}

// Verify TOTP code with optional window (allowed counter steps)
function tf_verify_totp(string $secret, string $code, int $window = 1): bool
{
    $code = preg_replace('/\s+/', '', $code);
    if (!ctype_digit($code)) return false;
    $digits = 6;
    $period = 30;
    $secretKey = base32_decode($secret);
    $time = floor(time() / $period);
    for ($i = -$window; $i <= $window; $i++) {
        $counter = $time + $i;
        // 8-byte big-endian counter
        $msg = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $msg, $secretKey, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncated = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        $otp = $truncated % pow(10, $digits);
        $otp = str_pad((string)$otp, $digits, '0', STR_PAD_LEFT);
        if (hash_equals($otp, str_pad($code, $digits, '0', STR_PAD_LEFT))) return true;
    }
    return false;
}

// Generate recovery codes (plain codes returned, hashes can be stored)
function tf_generate_recovery_codes(int $count = 8): array
{
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $raw = strtoupper(bin2hex(random_bytes(4)));
        $codes[] = substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
    }
    return $codes;
}

// Hash recovery code for storage
function tf_hash_recovery_code(string $code): string
{
    return password_hash($code, PASSWORD_DEFAULT);
}

// Verify recovery code against hashed rows (returns matching id or false)
function tf_verify_recovery_code(PDO $pdo, int $user_id, string $code)
{
    $st = $pdo->prepare('SELECT id, code_hash, used_at FROM user_recovery_codes WHERE user_id = ? AND used_at IS NULL');
    $st->execute([$user_id]);
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($code, $row['code_hash'])) {
            return $row['id'];
        }
    }
    return false;
}

// Mark recovery code used
function tf_mark_recovery_code_used(PDO $pdo, int $id): void
{
    $st = $pdo->prepare('UPDATE user_recovery_codes SET used_at = NOW() WHERE id = ?');
    $st->execute([$id]);
}

// Render QR image data URI if local QR lib exists, otherwise return an external Google Chart URL as fallback
function tf_qr_image_src(string $data, int $size = 220): string
{
    // Prefer chillerlan QR if installed (returns inline SVG)
    if (class_exists('\\Chillerlan\\QRCode\\QRCode')) {
        try {
            $options = new \Chillerlan\QRCode\QROptions([
                'outputType' => \Chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,
                'eccLevel'    => \Chillerlan\QRCode\QRCode::ECC_L,
                'scale'       => 5,
            ]);
            $q = new \Chillerlan\QRCode\QRCode($options);
            $svg = $q->render($data);
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (Exception $e) {
            // fall through to try other installed libraries
        }
    }

    // Prefer Endroid QR Code (v4+) if installed
    if (class_exists('Endroid\\QrCode\\QrCode') && class_exists('Endroid\\QrCode\\Writer\\PngWriter')) {
        try {
            // Create QR object and writer dynamically to avoid hard dependency in code if not installed
            $qrClass = 'Endroid\\QrCode\\QrCode';
            $writerClass = 'Endroid\\QrCode\\Writer\\PngWriter';
            $qr = $qrClass::create($data)->setSize($size)->setMargin(10);
            $writer = new $writerClass();
            $result = $writer->write($qr);
            if (method_exists($result, 'getDataUri')) {
                return $result->getDataUri();
            }
            // Fallback: return raw image data as data URI (PNG)
            if (method_exists($result, 'getString')) {
                $png = $result->getString();
                return 'data:image/png;base64,' . base64_encode($png);
            }
        } catch (Exception $e) {
            // fall through
        }
    }

    // If we get here, no supported local QR library is available. This is a hard security requirement: do NOT use external QR services.
    throw new \RuntimeException('No local QR-code library installed. Install one with composer, e.g. "composer require endroid/qr-code" or "composer require chillerlan/php-qrcode"');
}

/* ---------------------
 * Base32 encode/decode
 * --------------------- */
function base32_encode(string $data): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $padding = '=';
    $bits = 0;
    $value = 0;
    $output = '';
    $data_len = strlen($data);
    for ($i = 0; $i < $data_len; $i++) {
        $value = ($value << 8) | ord($data[$i]);
        $bits += 8;
        while ($bits >= 5) {
            $output .= $alphabet[($value >> ($bits - 5)) & 31];
            $bits -= 5;
        }
    }
    if ($bits > 0) {
        $output .= $alphabet[($value << (5 - $bits)) & 31];
    }
    $padlen = (8 - (strlen($output) % 8)) % 8;
    if ($padlen) $output .= str_repeat($padding, $padlen);
    return $output;
}

function base32_decode(string $b32): string
{
    $b32 = strtoupper($b32);
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = preg_replace('/[^A-Z2-7]+/', '', $b32);
    $bits = 0;
    $value = 0;
    $output = '';
    $len = strlen($b32);
    for ($i = 0; $i < $len; $i++) {
        $value = ($value << 5) + strpos($alphabet, $b32[$i]);
        $bits += 5;
        if ($bits >= 8) {
            $output .= chr(($value >> ($bits - 8)) & 255);
            $bits -= 8;
        }
    }
    return $output;
}

?>
