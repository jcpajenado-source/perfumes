<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - RRJJ Scents</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: #333;
            background-color: #fff;
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

            /* Show the menu when 'active' class is added */
            #navLinks.active {
                display: flex;
            }
        }

        /* About Us Section */
        .about-us-container {
            padding: 40px;
            background-color: #fff;
            min-height: 70vh;
        }

        .about-us-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 30px;
            position: relative;
        }

        .about-us-title::after {
            content: "";
            display: block;
            width: 15%;
            height: 2px;
            background-color: #991304;
            margin: 10px auto;
        }

        .about-us-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        /* Quote Styling */
        .quote {
            font-family: 'Georgia', serif;
            font-style: italic;
            font-size: 1.2rem;
            color: #555;
            background-color: #f7f7f7;
            border-left: 5px solid #991304;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .quote p {
            margin: 0;
        }

        /* Footer */
        footer {
            background-color: #000; 
            color: #fff;
            padding: 20px;
            font-size: 0.9rem;
            text-align: center;
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
            
            .about-us-container {
                padding: 20px;
            }
            
            .about-us-content {
                padding: 10px;
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

    <!-- Hamburger Menu Icon mobile view -->
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
        <li><a href="about.php" class="active">About</a></li>
    </ul>
</nav>

<!-- About Us Section -->
<div class="about-us-container">
    <div class="about-us-title">
        About Us
    </div>
    <div class="about-us-content">
        <p>At RRJJ Scents, we believe in providing only the best and most luxurious fragrances for all of our customers. We are a family-owned business that has been in the industry for over 10 years. Our collection of scents offers a wide range of high-quality perfumes and colognes that bring out your true essence and make you feel confident.</p>
        <p>We aim to bring you not just fragrances, but an experience. From the moment you enter our store or browse online, we want you to feel the care and passion we have for the art of perfumery. Join us on our journey to discover the perfect scent that matches your personality.</p>
        <p>Our mission is simple: to make every moment memorable with the perfect fragrance. Thank you for choosing RRJJ Scents, where every scent tells a story.</p>
    </div>

    <!-- Quote Section -->
    <div class="quote">
        <p>"Fragrance is the most intimate and powerful art form; it touches the soul and brings memories alive. At RRJJ Scents, we don't just sell perfumes—we offer an experience to remember."</p>
    </div>

    <div class="about-us-content">
        <p>We carefully curate each fragrance to embody a unique story. Our team of expert perfumers is dedicated to ensuring that each scent is crafted with the finest ingredients and designed to evoke emotions that last a lifetime. Whether you are shopping for a gift, a personal treat, or simply seeking to elevate your daily experience, we guarantee that our scents will leave a lasting impression.</p>
        <p>Our commitment to excellence has earned us the trust of customers worldwide. We are proud to be a leading brand in the fragrance industry, blending creativity, craftsmanship, and innovation. Thank you for allowing us to be part of your journey through scent.</p>
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
    // Function to toggle menu
    function toggleMenu() {
        const navLinks = document.getElementById("navLinks");
        navLinks.classList.toggle("active");
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