<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_header.php';

require_admin();

$pdo = getPDO();

$errors = [];

$types = [
    'text'       => 'Text',
    'textarea'   => 'Textarea',
    'number'     => 'Number',
    'select'     => 'Select',
    'multiselect' => 'Multi Select',
    'boolean'    => 'Yes / No',
    'date'       => 'Date',
    'color'      => 'Color'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    }

    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'text';

    $isRequired = isset($_POST['is_required']) ? 1 : 0;
    $isActive   = isset($_POST['is_active']) ? 1 : 0;

    $optionsText = trim($_POST['options'] ?? '');

    if ($name === '') {
        $errors[] = 'Attribute name is required.';
    }

    if (!array_key_exists($type, $types)) {
        $errors[] = 'Invalid attribute type.';
    }

    if (empty($errors)) {

        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim($slug, '-');

        // Check duplicate slug
        $stmt = $pdo->prepare("
            SELECT id
            FROM attributes
            WHERE slug = ?
            LIMIT 1
        ");

        $stmt->execute([$slug]);

        if ($stmt->fetch()) {
            $errors[] = 'An attribute with this name already exists.';
        }
    }

    if (empty($errors)) {

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO attributes
                (
                    name,
                    slug,
                    type,
                    is_required,
                    is_active
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $slug,
                $type,
                $isRequired,
                $isActive
            ]);

            $attributeId = (int)$pdo->lastInsertId();

            /*
             * Options are only applicable to:
             * select / multiselect
             */
            if (
                in_array($type, ['select', 'multiselect'], true)
                && $optionsText !== ''
            ) {

                $options = preg_split(
                    '/\r\n|\r|\n/',
                    $optionsText
                );

                $optionStmt = $pdo->prepare("
                    INSERT INTO attribute_options
                    (
                        attribute_id,
                        option_value,
                        sort_order
                    )
                    VALUES (?, ?, ?)
                ");

                $sortOrder = 0;

                foreach ($options as $option) {

                    $option = trim($option);

                    if ($option === '') {
                        continue;
                    }

                    $optionStmt->execute([
                        $attributeId,
                        $option,
                        $sortOrder++
                    ]);
                }
            }

            $pdo->commit();

            header('Location: index.php');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = 'Unable to create attribute.';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>
        <h3 class="mb-1">Add Attribute</h3>
        <div class="text-muted small">
            Create a reusable product attribute.
        </div>
    </div>

    <a href="index.php" class="btn btn-outline-secondary">
        Back
    </a>

</div>

<?php if ($errors): ?>

    <div class="alert alert-danger">

        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>

    </div>

<?php endif; ?>

<div class="card admin-card">

    <div class="card-body p-4">

        <form method="post">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= csrf_token() ?>">

            <div class="mb-3">

                <label class="form-label">
                    Attribute Name
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="e.g. ISBN, Author, Size, Color"
                    value="<?= esc($_POST['name'] ?? '') ?>"
                    required>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Type
                </label>

                <select
                    name="type"
                    id="attributeType"
                    class="form-select"
                    required>

                    <?php foreach ($types as $value => $label): ?>

                        <option
                            value="<?= esc($value) ?>"
                            <?= ($_POST['type'] ?? 'text') === $value
                                ? 'selected'
                                : '' ?>>

                            <?= esc($label) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div
                class="mb-3"
                id="optionsContainer"
                style="display:none;">

                <label class="form-label">
                    Options
                </label>

                <textarea
                    name="options"
                    class="form-control"
                    rows="6"
                    placeholder="One option per line&#10;&#10;Example:&#10;English&#10;Bengali&#10;Hindi"><?= esc($_POST['options'] ?? '') ?></textarea>

                <div class="form-text">
                    Enter one option per line.
                </div>

            </div>

            <div class="form-check mb-2">

                <input
                    type="checkbox"
                    name="is_required"
                    class="form-check-input"
                    id="isRequired"
                    <?= isset($_POST['is_required']) ? 'checked' : '' ?>>

                <label
                    class="form-check-label"
                    for="isRequired">

                    Required field

                </label>

            </div>

            <div class="form-check mb-4">

                <input
                    type="checkbox"
                    name="is_active"
                    class="form-check-input"
                    id="isActive"
                    <?= !isset($_POST['is_active']) || isset($_POST['is_active'])
                        ? 'checked'
                        : '' ?>>

                <label
                    class="form-check-label"
                    for="isActive">

                    Active

                </label>

            </div>

            <button
                type="submit"
                class="btn btn-primary">

                Save Attribute

            </button>

            <a
                href="index.php"
                class="btn btn-outline-secondary">

                Cancel

            </a>

        </form>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const type = document.getElementById('attributeType');
    const options = document.getElementById('optionsContainer');

    function updateOptionsVisibility() {

        const needsOptions =
            type.value === 'select' ||
            type.value === 'multiselect';

        options.style.display =
            needsOptions ? 'block' : 'none';
    }

    type.addEventListener('change', updateOptionsVisibility);

    updateOptionsVisibility();

});
</script>