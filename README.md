# Simple PHP E-Commerce System

A lightweight, flat-file PHP e-commerce platform built with native PHP, MySQL, and Bootstrap. Perfect for learning and rapid prototyping.

## 📋 Features

- **User Authentication**: Secure registration & login with password hashing
- **Two User Roles**: Admin (manage products & view all orders) & Member (shop & order)
- **Persistent Cart**: Database-backed shopping cart per user
- **Product Management**: Full CRUD operations for admins
- **Order System**: Complete checkout flow with automatic stock deduction
- **Order History**: Users can view their past purchases with line-item details
- **Flash Messages**: User feedback on all actions (success/error/warning)
- **Bootstrap UI**: Responsive design with Bootstrap 5 CDN

## 🗂️ Project Structure

```
ecommerce/
├── config.php           # Database credentials & app settings
├── db.php               # PDO connection + helper functions
├── schema.sql           # Database table definitions
├── index.php            # Product listing page
├── register.php         # User registration
├── login.php            # User login
├── logout.php           # Session destroy
├── cart.php             # Shopping cart with checkout
├── history.php          # Order history (member only)
├── admin.php            # Admin dashboard (admin only)
└── README.md            # This file
```

## 🗄️ Database Schema

### Tables
- **users**: Stores user accounts (admin/member roles)
- **products**: Product catalog with stock tracking
- **transactions**: Order headers (user + total)
- **transaction_items**: Order line items (product + qty + price)
- **carts**: Persistent shopping cart per user

## 🚀 Setup Instructions

### 1. Prerequisites
- PHP 8.0+ (with PDO MySQL extension)
- MySQL 5.7+
- Web server (Apache, Nginx, or PHP built-in server)

### 2. Database Setup

Create a new MySQL database:
```sql
CREATE DATABASE ecommerce;
```

Import the schema:
```bash
mysql -u root ecommerce < schema.sql
```

Or copy-paste the SQL from `schema.sql` in your MySQL client.

### 3. Configure Database Credentials

Edit `config.php`:
```php
define('DB_HOST', 'localhost');      // Your MySQL host
define('DB_USER', 'root');           // Your MySQL user
define('DB_PASS', '');               // Your MySQL password
define('DB_NAME', 'ecommerce');      // Your database name
```

### 4. Run the Application

**Option A: Built-in PHP Server** (easiest for local development)
```bash
cd /path/to/ecommerce
php -S localhost:8000
```
Then open http://localhost:8000 in your browser.

**Option B: Apache/Nginx**
Place the folder in your webroot (e.g., `/var/www/html/ecommerce`) and access via `http://localhost/ecommerce`.

## 👤 Default Admin Account

After running `schema.sql`, a pre-seeded admin account is created:

**Email**: `admin@example.com`  
**Password**: `admin123`

You can create additional admins by registering new users and manually updating their role to `admin` in the database.

## 🧠 User Workflows

### Member (Customer)
1. **Register** → Provide name, email, password
2. **Login** → Access product catalog
3. **Browse** → View all products with stock levels
4. **Add to Cart** → Select quantity and add items
5. **Cart** → Update quantities, remove items, see total
6. **Checkout** → Creates transaction, deducts stock, clears cart
7. **Order History** → View past orders with full details

### Admin
1. **Login** → Redirected to admin dashboard
2. **View Transactions** → See all orders from all users
3. **Manage Products** → Add, edit, or delete products
4. **Adjust Stock** → Update inventory levels
5. **Access Control** → Cannot access member pages (hard block)

## 🔐 Security Features

- **Password Hashing**: bcrypt with `password_hash()` & `password_verify()`
- **SQL Injection Prevention**: Prepared statements with PDO
- **XSS Protection**: `htmlspecialchars()` on all user output
- **Session-Based Auth**: User data stored in `$_SESSION`
- **Role-Based Access Control**: Admin pages check `$_SESSION['role']`

## 📝 Code Highlights

### Cart Persistence (Database-Backed)
Instead of session arrays, each item is a row in the `carts` table:
```php
INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)
```

### Checkout with Transactions
The checkout process uses database transactions to ensure atomicity:
- All items added to `transaction_items`
- Stock decremented for all products
- Cart cleared only if all operations succeed
- Rolled back if any operation fails

### Flash Messages
Feedback is stored in session and cleared after display:
```php
setFlash('Your message', 'success');  // success, warning, danger, info
header('Location: index.php');
// On next page load:
$flash = getFlash();  // Returns and clears the message
```

### Helper Functions (in db.php)
- `isLoggedIn()` → Check if user is authenticated
- `isAdmin()` → Check if user is admin
- `requireLogin()` → Redirect if not logged in
- `requireAdmin()` → Die if not admin
- `setFlash($msg, $type)` → Store a flash message
- `getFlash()` → Retrieve and clear a flash message

## 🎨 Styling

All pages use **Bootstrap 5** via CDN for instant, responsive styling. Custom CSS is minimal and inline in `<style>` blocks. No build process required.

## 🧪 Testing Checklist

- [ ] Create a new member account
- [ ] Add items to cart
- [ ] Verify cart persists in `carts` table
- [ ] Checkout and verify transaction created
- [ ] Check stock decreased in database
- [ ] View order in Order History
- [ ] Login as admin and view all transactions
- [ ] Add a new product as admin
- [ ] Edit product stock
- [ ] Delete a product

## 📚 Future Enhancements

- **Payment Gateway Integration**: Connect to Stripe/PayPal (currently mocked)
- **Email Notifications**: Send order confirmations via SMTP
- **Product Images Upload**: File upload instead of URL-only
- **Search & Filtering**: Find products by name, price range, category
- **User Profiles**: Edit personal details, change password
- **Wishlist**: Save favorite products
- **Reviews & Ratings**: Customer product feedback
- **Pagination**: Handle large product/order lists
- **API Layer**: REST endpoints for mobile app

## 🐛 Troubleshooting

### "Database connection failed"
- Check `config.php` credentials match your MySQL setup
- Ensure MySQL is running
- Verify database and tables exist (`schema.sql` imported)

### "Access Denied" on admin page
- Make sure you're logged in as an admin account
- Check `users.role` in database is `'admin'`

### Cart items not persisting
- Verify `carts` table exists and has correct schema
- Check user is logged in (`$_SESSION['user_id']` is set)
- Confirm no database errors in PHP error log

### "Class 'PDO' not found"
- PHP doesn't have PDO MySQL extension installed
- On Ubuntu: `sudo apt-get install php-mysql`
- On macOS: Ensure PHP was installed with MySQL support

## 📄 License

This project is provided as-is for educational purposes. Feel free to modify and distribute.

## 📞 Support

For issues or questions, review the code comments in each file or refer to the PHP/MySQL documentation.

---

**Built with ❤️ for simplicity and learning**
