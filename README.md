# Student Portal Blog

A PHP/MySQL student portal and blog system built with XAMPP-compatible PHP and MariaDB/MySQL.

## Overview

This project is a student portal blog application where users can register, login, publish posts, and view published content. It includes admin functions for managing users and blog content.

## Key Features

- User registration and login
- User dashboard with profile details
- Blog listing and blog view pages
- Blog post creation with categories, tags, and image upload
- Admin user management (create, update, delete, reset password)
- Dashboard and blog system interface
- Database initialization script for easy setup

## Requirements

- PHP 7.4+ / PHP 8.x
- MySQL or MariaDB
- Web server or PHP built-in server
- Browser access to `http://localhost`

## Installation

1. Copy the project files into your web server root.
   - Example XAMPP path: `C:\xampp\htdocs\dwit-swagat`

2. Ensure XAMPP Apache and MySQL services are running.

3. Configure the database connection.
   - If you are setting up a new copy, copy `config_sample.php` to `config.php`.
   - Update the credentials in `config.php` if needed.

```php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'swagat';
```

4. Initialize the database.
   - Open `http://localhost/dwit-swagat/init.php` in your browser.
   - This script will create the `swagat` database, tables, and required upload directories.

> Only run the initialization script once.

## Running Locally

### Option 1: Using XAMPP

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Open `http://localhost/dwit-swagat` in your browser.

### Option 2: Using PHP Built-in Server

From the project root directory:

```powershell
cd "C:\xampp\htdocs\dwit-swagat"
& "C:\xampp\php\php.exe" -S localhost:8000
```

Then visit:

- `http://localhost:8000`
- or `http://localhost/dwit-swagat` if using Apache

## Default Pages

- `index.php` - Home / landing page
- `register.php` - User registration
- `login.php` - User login
- `dashboard.php` - User dashboard
- `blog_list.php` - Blog posts list
- `blog_view.php` - Single post view
- `blog_create.php` - Create a blog post
- `admin_users.php` - Admin user management
- `logout.php` - Logout

## Database Setup

The application expects a database named `swagat` with these tables (created via `init.php`):

- `users`
- `blog_posts`
- `blog_categories`
- `post_categories`
- `blog_comments`
- `blog_likes`
- `comment_likes`
- `blog_tags`
- `post_tags`

## File Structure

- `config.php` - Database connection and session start
- `init.php` - Database and directory initialization script
- `includes/header.php` / `includes/footer.php` - Shared page layout
- `assets/css/styles.css` - Styling for the frontend
- `uploads/blog/images/` - Uploaded blog images
- `uploads/blog/thumbnails/` - Generated thumbnails

## Notes

- The default database user is `root` with an empty password for local development.
- If database connection fails, verify the MySQL service is running and credentials in `config.php` are correct.
- Do not leave `init.php` accessible in production after setup.

## Troubleshooting

- If you see a connection error, make sure MySQL is running on `localhost:3306`.
- If the app cannot write uploads, ensure the `uploads/` directory has write permissions.
- If the site returns PHP errors, enable display errors in your PHP configuration or check the web server logs.

## License

This project is provided as-is for local development and learning purposes.
