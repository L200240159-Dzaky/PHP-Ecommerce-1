# Ecommerce

Vanilla PHP ecommerce starter for XAMPP.

## Features
- Public product listing
- User registration and login
- Guest, member, and admin role handling
- Admin product CRUD
- PDO + prepared statements
- CSRF-protected forms

## Setup
1. Create the MySQL database and tables:
   - Import `database/schema.sql` in phpMyAdmin, or run it against MySQL.
2. Update credentials if needed in `config/config.php`.
3. Open the site in XAMPP:
   - `http://localhost/Ecommerce/`

## Default admin
- Email: `admin@example.com`
- Password: `Admin123!`

## Notes
- Guest users can browse the storefront.
- Members can log in and access the dashboard.
- Admins can manage products from `admin/products.php`.
