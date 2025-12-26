<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - RRJJ Scents</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: #333;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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
            padding: 8px 25px;
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

        /* Active link styling */
        nav ul li a.active {
            background-color: #991304;
            color: white;
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

        /* Privacy Policy Section */
        .privacy-policy-container {
            padding: 40px;
            background-color: #fff;
            flex: 1;
        }

        .privacy-policy-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 30px;
            position: relative;
        }

        .privacy-policy-title::after {
            content: "";
            display: block;
            width: 15%;
            height: 2px;
            background-color: #991304;
            margin: 10px auto;
        }

        .privacy-policy-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: left;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            margin-top: 25px;
            color: #991304;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        ul {
            list-style-type: disc;
            margin-left: 20px;
            padding-left: 0;
            margin-bottom: 20px;
        }

        ul li {
            margin-bottom: 10px;
            padding-left: 5px;
        }

        .last-updated {
            text-align: center;
            font-style: italic;
            color: #666;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        footer {
            background-color: #000;
            color: #fff;
            padding: 20px;
            font-size: 0.9rem;
            text-align: center;
            margin-top: auto;
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
            
            .privacy-policy-container {
                padding: 20px;
            }
            
            .privacy-policy-content {
                padding: 10px;
            }
        }

        @media (max-width: 480px) {
            .privacy-policy-title {
                font-size: 1.3rem;
            }
            
            h3 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav>
    <div class="logo">
        <img src="images/kai5.png" alt="RRJJ Scents Logo">
        <h1>RRJJ Scents</h1>
    </div>

    <!-- Hamburger Menu Icon -->
    <div class="hamburger" onclick="toggleMenu()">
        <div></div>
        <div></div>
        <div></div>
    </div>

    <!-- Navigation Links -->
    <ul id="navLinks">
        <li><a href="index.php">Home</a></li>
        <li><a href="collections.php">Collections</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="privacy.php" class="active">Privacy</a></li>
    </ul>
</nav>

<!-- Privacy Policy Section -->
<div class="privacy-policy-container">
    <div class="privacy-policy-title">
        Privacy Policy
    </div>
    <div class="privacy-policy-content">
        <p>At RRJJ Scents, we are committed to protecting your privacy. This Privacy Policy outlines the types of personal information we collect, how we use it, and the measures we take to safeguard it. By using our website, you agree to the terms of this policy.</p>

        <h3>1. Information We Collect</h3>
        <p>We collect personal information from you when you visit our site, place an order, subscribe to our newsletter, or contact us. The types of information we collect include:</p>
        <ul>
            <li>Personal identification details (e.g., name, email address, phone number, postal address).</li>
            <li>Payment information (e.g., credit card details, billing address, and transaction history).</li>
            <li>Non-personal data such as browser type, IP address, and browsing history on our site.</li>
            <li>Communication information (e.g., emails, chats, or phone calls with customer support).</li>
        </ul>

        <h3>2. How We Use Your Information</h3>
        <p>Your information is used in the following ways:</p>
        <ul>
            <li>To process your transactions and deliver products to your address.</li>
            <li>To communicate with you about your orders, promotions, and special offers.</li>
            <li>To improve our website and services by understanding customer preferences and behavior.</li>
            <li>To provide customer support and respond to inquiries.</li>
            <li>To personalize your shopping experience and recommend products you may be interested in.</li>
        </ul>

        <h3>3. Sharing Your Information</h3>
        <p>We do not sell, trade, or rent your personal information to third parties. However, we may share your information with trusted third-party service providers who help us operate our business and fulfill orders, such as:</p>
        <ul>
            <li>Payment processors to complete your transactions.</li>
            <li>Shipping companies to deliver your products.</li>
            <li>Marketing partners for sending promotional emails (only with your consent).</li>
        </ul>
        <p>We ensure that all third-party partners are compliant with applicable privacy laws and regulations and are required to keep your information confidential.</p>

        <h3>4. Data Retention</h3>
        <p>We retain your personal data only for as long as necessary to fulfill the purposes for which it was collected, including for the purpose of satisfying any legal, accounting, or reporting requirements. After that period, your data will be securely deleted or anonymized.</p>

        <h3>5. Your Rights</h3>
        <p>You have the right to:</p>
        <ul>
            <li>Access and receive a copy of the personal information we hold about you.</li>
            <li>Request corrections or updates to your personal information.</li>
            <li>Request the deletion of your personal information (subject to certain legal obligations).</li>
            <li>Opt out of marketing communications at any time.</li>
        </ul>

        <h3>6. Security</h3>
        <p>We implement a variety of security measures to maintain the safety of your personal information. These measures include encryption, secure payment processing, and regular security audits to prevent unauthorized access.</p>

        <h3>7. Cookies</h3>
        <p>We use cookies to enhance your browsing experience on our website. Cookies are small files stored on your device that help us analyze website traffic and remember your preferences. You can choose to disable cookies through your browser settings, but this may affect the functionality of certain features on our website.</p>

        <h3>8. Third-Party Links</h3>
        <p>Our website may contain links to third-party sites. We are not responsible for the privacy practices of these sites, and we encourage you to review their privacy policies before providing any personal information.</p>

        <h3>9. Changes to This Privacy Policy</h3>
        <p>We may update this Privacy Policy from time to time. Any changes will be posted on this page, and the date of the most recent revision will be updated at the top of the policy. Please review this page regularly to stay informed about how we are protecting your personal data.</p>
        
        <div class="last-updated">
            <p><strong>Last Updated:</strong> <?php echo date('F j, Y'); ?></p>
        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    <p>&copy; <?php echo date('Y'); ?> RRJJ Scents. All rights reserved.</p>
    <ul class="footer-links">
        <li><a href="privacy.php">Privacy Policy</a></li>
        <li><a href="terms.php">Terms of Service</a></li>
        <li><a href="contact.php">Contact</a></li>
    </ul>
</footer>

<script>
    function toggleMenu() {
        const navLinks = document.getElementById('navLinks');
        navLinks.classList.toggle('active'); 
    }
    
    // Close menu when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const navLinks = document.getElementById("navLinks");
        const hamburger = document.querySelector('.hamburger');
        
        if (window.innerWidth <= 768) {
            if (!hamburger.contains(event.target) && !navLinks.contains(event.target)) {
                navLinks.classList.remove('active');
            }
        }
    });
    
    // Close menu when window is resized above mobile breakpoint
    window.addEventListener('resize', function() {
        const navLinks = document.getElementById("navLinks");
        if (window.innerWidth > 768) {
            navLinks.classList.remove('active');
        }
    });
</script>
    
</body>
</html>