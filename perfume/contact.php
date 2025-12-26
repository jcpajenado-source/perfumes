<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Handle contact form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Basic validation
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address";
    if (empty($message)) $errors[] = "Message is required";
    
    // If no errors, process the contact form
    if (empty($errors)) {
        // In a real application, you would:
        // 1. Save to database
        // 2. Send email notification
        // 3. Return success message
        
        $_SESSION['contact_success'] = true;
        header("Location: contact.php?success=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Scentrix Parfum</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: #333;
            background-color: #f9f9f9;
        }

        /* Navigation Bar */
        nav {
            background-color: #000; 
            padding: 0.5rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky; 
            top: 0; 
            z-index: 1000; 
        }

        .logo {
            display: flex;
            align-items: center;
            font-family: 'Roboto', sans-serif;
        }

        .logo img {
            height: 30px;
            margin-right: 10px;
        }

        .logo h1 {
            font-size: 1.2rem;
            margin: 0;
            font-weight: normal;
            color: #fff;
        }

        nav ul {
            list-style-type: none;
            display: flex;
            align-items: center;
        }

        nav ul li {
            margin-left: 12px;
        }

        nav ul li a {
            text-decoration: none;
            color: #f0f0f0;
            padding: 8px 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
            letter-spacing: 1px;
        }

        nav ul li a:hover {
            background-color: #444;
        }

        /* User Welcome Message */
        .welcome-message {
            background-color: #f0f0f0;
            padding: 10px 20px;
            text-align: center;
            font-size: 14px;
            color: #333;
        }

        .welcome-message a {
            color: #991304;
            text-decoration: none;
            font-weight: bold;
            margin-left: 10px;
        }

        .welcome-message a:hover {
            text-decoration: underline;
        }

        /* Hamburger Menu */
        .hamburger {
            display: none; 
            flex-direction: column;
            justify-content: space-between;
            width: 25px;
            height: 20px;
            cursor: pointer;
            position: absolute;
            right: 20px;
            top: 18px;
        }

        .hamburger div {
            height: 3px;
            background-color: #fff;
        }

        #navLinks {
            display: flex; 
            flex-direction: row;
            position: relative;
            text-align: center;
        }

        #navLinks li {
            margin: 10px 0;
        }

        @media screen and (max-width: 768px) {
            nav ul {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            #navLinks {
                display: none;
                position: absolute;
                top: 60px;
                left: 0;
                right: 0;
                background-color: #000;
                padding: 1rem;
                width: 100%;
                text-align: center;
                flex-direction: column;
            }

            #navLinks li {
                margin: 12px 0;
            }

            #navLinks.active {
                display: flex; 
            }
        }

        /* Contact Us Section */
        .contact-us-container {
            padding: 40px 20px;
            background-color: #fff;
            max-width: 1200px;
            margin: 0 auto;
        }

        .contact-us-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 30px;
            position: relative;
        }

        .contact-us-title::after {
            content: "";
            display: block;
            width: 15%;
            height: 2px;
            background-color: #991304;
            margin: 10px auto;
        }

        .contact-us-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .contact-info {
            text-align: center;
            margin-top: 30px;
        }

        .contact-info p {
            font-size: 1.1rem;
            margin: 15px 0;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .social-links a {
            display: inline-block;
        }

        .social-links img {
            width: 40px;
            height: 40px;
            transition: opacity 0.3s ease-in-out;
        }

        .social-links img:hover {
            opacity: 0.7;
        }

        /* Contact Form */
        .contact-form-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .contact-form h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .contact-form textarea {
            height: 150px;
            resize: vertical;
        }

        .contact-form button {
            width: 100%;
            padding: 12px;
            background-color: #991304;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .contact-form button:hover {
            background-color: #7a0f03;
        }

        /* Success Message */
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px auto;
            max-width: 600px;
            text-align: center;
            font-size: 14px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px auto;
            max-width: 600px;
            text-align: center;
            font-size: 14px;
        }

        .error-message ul {
            list-style: none;
            padding: 0;
            margin: 10px 0 0 0;
        }

        .error-message li {
            margin: 5px 0;
        }

        /* Google Maps Embed */
        .map-container {
            margin-top: 40px;
            text-align: center;
        }

        .map-container h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            margin-top: 40px;
        }

        .map-container iframe {
            width: 100%;
            max-width: 800px;
            height: 400px;
            border: none;
            border-radius: 8px;
        }

        /* Customer Support */
        .customer-support {
            text-align: center;
            margin-top: 40px;
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
        }

        .customer-support h2 {
            font-size: 1.2rem;
            margin-bottom: 15px;
        }

        .customer-support p {
            font-size: 1.1rem;
            margin: 10px 0;
        }

        .customer-support a {
            text-decoration: none;
            font-weight: bold;
            color: #991304;
            margin-top: 10px;
            display: inline-block;
            font-size: 1.1rem;
        }

        .customer-support a:hover {
            text-decoration: underline;
        }

        /* Thank You / Welcoming Customers Section */
        .thank-you {
            text-align: center;
            margin-top: 40px;
        }

        .thank-you h2 {
            font-size: 1.2rem;
            color: #991304;
            font-weight: 700;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .thank-you p {
            font-size: 1rem;
            color: #333;
            line-height: 1.8;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Footer */
        footer {
            background-color: #000;
            color: #fff;
            padding: 20px;
            font-size: 0.9rem;
            text-align: center;
            margin-top: 40px;
        }

        footer p {
            margin: 10px 0;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            display: inline;
            margin: 0 10px;
        }

        .footer-links li a {
            color: #fff;
            text-decoration: none;
            text-transform: uppercase;
            font-weight: bold;
            transition: color 0.3s;
        }

        .footer-links li a:hover {
            color: #991304;
        }

        @media (max-width: 768px) {
            footer {
                font-size: 0.8rem;
            }
            
            .contact-us-container {
                padding: 20px 10px;
            }
            
            .contact-form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- User Welcome Message -->
    <div class="welcome-message">
        Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 
        <a href="logout.php">Logout</a>
    </div>

    <!-- Navigation Bar -->
    <nav>
        <div class="logo">
            <img src="images/kai5.png" alt="Logo">
            <h1>Scentrix Parfum</h1>
        </div>

        <!-- Hamburger Menu Icon -->
        <div class="hamburger" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>

        <!-- Navigation Links -->
        <ul id="navLinks">
            <li><a href="homepage.php">Home</a></li>
            <li><a href="collections.php">Collections</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="myprofile.php"><?php echo htmlspecialchars($_SESSION['first_name']); ?></a></li>
            <li><a href="cart.php">Cart</a></li>
        </ul>
    </nav>

    <!-- Success Message -->
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="success-message">
            Thank you for your message! We'll get back to you as soon as possible.
        </div>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="error-message">
            <strong>Please fix the following errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Contact Us Section -->
    <div class="contact-us-container">
        <div class="contact-us-title">
            Contact Us
        </div>
        <div class="contact-us-content">
            <p>If you have any questions, concerns, or inquiries, feel free to get in touch with us using the information below. We are here to assist you!</p>
        </div>

        <!-- Contact Information -->
        <div class="contact-info">
            <p><strong>Our Location:</strong> 123 Fragrance Avenue, Perfume City, SC 12345</p>
            <p><strong>Phone:</strong> (123) 456-7890</p>
            <p><strong>Email:</strong> <a href="mailto:contact@scentrixparfum.com">contact@scentrixparfum.com</a></p>
        </div>

        <!-- Social Media Links -->
        <div class="social-links">
            <a href="https://facebook.com/scentrixparfum" target="_blank">
                <img src="images/facebook.png" alt="Facebook">
            </a>
            <a href="https://instagram.com/scentrixparfum" target="_blank">
                <img src="images/instagram.png" alt="Instagram">
            </a>
            <a href="https://twitter.com/scentrixparfum" target="_blank">
                <img src="images/twitter.png" alt="Twitter">
            </a>
        </div>

        <!-- Contact Form -->
        <div class="contact-form-container">
            <h2>Send us a Message</h2>
            <form class="contact-form" method="POST" action="">
                <input type="text" name="name" placeholder="Your Name" 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>" required>
                <input type="email" name="email" placeholder="Your Email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($_SESSION['user_email']); ?>" required>
                <input type="text" name="subject" placeholder="Subject" 
                       value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" required>
                <textarea name="message" placeholder="Your Message" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                <button type="submit">Send Message</button>
            </form>
        </div>

        <!-- Google Maps Embed -->
        <div class="map-container">
            <h2>Find Us on the Map</h2>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.987125270232!2d121.01425397502976!3d14.554164777052648!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c8f3fa2994c1%3A0x6d4c6e30b1c848d5!2sMakati%2C%20Metro%20Manila!5e0!3m2!1sen!2sph!4v1700000000000!5m2!1sen!2sph" 
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>

        <!-- Customer Support Section -->
        <div class="customer-support">
            <h2>Need Assistance?</h2>
            <p>Our customer support team is available to help you with any issues or inquiries you may have. Feel free to reach out!</p>
            <p><strong>Support Email:</strong> <a href="mailto:support@scentrixparfum.com">support@scentrixparfum.com</a></p>
            <p><strong>Customer Support Hours:</strong> Monday to Friday, 9:00 AM - 6:00 PM (PHT)</p>
            <a href="mailto:support@scentrixparfum.com">Contact Support</a>
        </div>

        <!-- Thank You / Welcoming Customers Section -->
        <div class="thank-you">
            <h2>Thank You for Your Support!</h2>
            <p>We truly appreciate our wonderful customers. Your feedback, questions, and comments help us improve and serve you better. We look forward to hearing from you!</p>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Scentrix Parfum. All rights reserved.</p>
        <ul class="footer-links">
            <li><a href="privacy.php">Privacy Policy</a></li>
            <li><a href="terms.php">Terms of Service</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </footer>

    <script>
        // Function to toggle menu
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }
        
        // Close menu when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const navLinks = document.getElementById("navLinks");
            const hamburger = document.querySelector('.hamburger');
            
            if (window.innerWidth <= 768) {
                if (!navLinks.contains(event.target) && !hamburger.contains(event.target)) {
                    navLinks.classList.remove('active');
                }
            }
        });
    </script>

</body>
</html>