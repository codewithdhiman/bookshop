# Bookshop (PHP + MySQL)

Simple online bookstore built with PHP 8, MySQL (PDO), Bootstrap 5 and vanilla JS.

Requirements
- PHP 8.0+
- MySQL 8+
- Apache (XAMPP/WAMP)

Setup
1. Copy the project to your web root (e.g., `d:/wamp/www/bookshop`).
2. Import the database: using phpMyAdmin or mysql client, run `database/schema.sql`.
3. Edit `config/database.php` to set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
4. If your app is in a subfolder (e.g., `/bookshop`), edit `BASE_URL` in `config/database.php` to match the folder (default: `/bookshop`).
5. Ensure `public/uploads/` is writable for image uploads.
6. Visit `http://localhost/bookshop/`.

Admin
- Default admin (sample) email: `admin@bookshop.local` password: `password` (change after import).

Folder structure
- `admin/` - admin panel
- `config/` - database config
- `includes/` - header/footer/helpers
- `public/` - css, js, uploads
- `database/schema.sql` - schema and sample data

Features
- Browse, search, filter products
- Product details
- Shopping cart (session)
- Checkout with server-side order creation and stock validation
- Admin login and basic dashboard

Security notes
- Uses PDO prepared statements and CSRF tokens
- Passwords hashed with `password_hash()`
