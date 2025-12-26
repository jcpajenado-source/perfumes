Perfume E-commerce Platform
Project Description
A fully-featured perfume e-commerce platform with user authentication, product management, shopping cart functionality, and administrative controls. The system allows customers to browse perfumes, add items to cart, and make purchases while providing administrators with tools to manage products and users.

Technologies Used
Frontend: HTML, CSS, JavaScript, Bootstrap

Backend: PHP (Object-Oriented Programming)

Database: MySQL with MySQLi Prepared Statements

Additional: jQuery for AJAX functionality

Features
Customer Features
User registration and authentication

Browse perfume collections by category

Product search and filtering

Shopping cart with add/remove functionality

Order placement and management

User profile management

Contact form for inquiries

Admin Features
Admin dashboard with analytics

Product management (CRUD operations)

User management and role control

Order processing and tracking

Inventory management

Security Features
Password encryption

SQL injection prevention using prepared statements

Session management

Input validation and sanitization

Installation Instructions
Prerequisites
PHP 7.4 or higher

MySQL 5.7 or higher

Apache/Nginx web server

Composer (optional)

Step-by-Step Installation
Clone the Repository

bash
git clone <repository-url>
cd perfume-ecommerce
Import Database

bash
mysql -u username -p database_name < perfume.sql
Or using phpMyAdmin:

Create a new database named perfume_db

Import the perfume.sql file from the /database folder

Configure Database Connection
Edit db.php with your database credentials:

php
<?php
$host = 'localhost';
$username = 'your_username';
$password = 'your_password';
$database = 'perfume_db';
?>
Configure File Permissions

bash
chmod 755 images/products/
chmod 644 images/products/*.jpg
Start Local Server

bash
php -S localhost:8000
Or configure your preferred web server (Apache/Nginx).

Access the Application

Main site: http://localhost:8000

Admin panel: http://localhost:8000/admin.php

Admin Login Credentials
Note: Use the credentials seeded in the database. Default admin credentials:

Username: admin@perfume.com

Password: Admin@123 (This is a sample - actual password is in database seeds)

Project Structure
text
perfume-ecommerce/
│
├── /images/products/          # Product images
│   ├── 693f9be16b6fc_1765776353.jpg
│   └── ... (other product images)
│
├── /docs/                     # Documentation (PDFs)
│   └── project_documentation.pdf
│
├── /database/                 # Database files
│   └── perfume.sql
│
├── about.php                  # About page
├── admin_products.php         # Admin product management
├── admin_users.php            # Admin user management
├── admin.php                  # Admin dashboard
├── cancel_order.php           # Order cancellation
├── cart.php                   # Shopping cart
├── collections.php            # Product collections
├── contact.php                # Contact page
├── db.php                     # Database configuration
├── edit_profile.php           # Profile editing
├── get_product.php            # Product details API
├── home-but.html              # Home button component
├── homebut.php                # Home navigation
├── homepage.php               # Main homepage
├── index.php                  # Application entry point
├── logout.php                 # Logout functionality
├── myprofile.php              # User profile
├── privacy.php                # Privacy policy
├── signup_process.php         # Registration processing
├── signup.php                 # Registration page
├── terms.php                  # Terms and conditions
├── perfume.sql                # Database dump
└── README.md                  # This file
File Descriptions
Core Pages
index.php - Application entry point with login/registration

homepage.php - Main customer homepage with product listings

admin.php - Admin dashboard with site statistics

User Management
signup.php - User registration form

signup_process.php - Processes registration data

myprofile.php - Displays user profile information

edit_profile.php - Profile editing interface

logout.php - Session destruction and logout

Product Management
admin_products.php - Admin product CRUD operations

collections.php - Product category browsing

get_product.php - AJAX endpoint for product details

Shopping Features
cart.php - Shopping cart management

cancel_order.php - Order cancellation functionality

Administrative
admin_users.php - User management for administrators

Static Content
about.php - Company information

contact.php - Contact form and information

privacy.php - Privacy policy

terms.php - Terms and conditions

Database Schema
Key tables include:

users - User accounts and authentication

products - Product information and inventory

categories - Product categories

orders - Order records

order_items - Individual items in orders

cart - Shopping cart temporary storage

Configuration
Update db.php with your database credentials

Ensure the images/products/ directory is writable

Configure your web server to point to the project root

Set appropriate file permissions for uploads

Developer Information
Group Details
Group Name: Perfume E-commerce Team

Course: Web Development Project

Institution: [Your Institution Name]

Team Members
Risha Gonzales

Role: Lead Developer & Database Architect

Responsibilities: Backend development, database design, security implementation

Security Notes
Always use the provided prepared statements to prevent SQL injection

Store passwords using PHP's password_hash() function

Validate all user inputs on both client and server side

Use HTTPS in production environments

Regularly update dependencies and PHP version

Troubleshooting
Common Issues
Database Connection Failed

Verify credentials in db.php

Check MySQL service is running

Ensure database exists and user has privileges

Images Not Loading

Check file permissions in images/products/

Verify image paths in the database

Session Issues

Check PHP session configuration

Ensure cookies are enabled in browser

Admin Access Denied

Verify admin credentials in database

Check user role permissions

Support
For issues not covered here, please check the /docs folder for detailed documentation or contact the development team.

License
This project is developed for educational purposes. All rights reserved by the development team.