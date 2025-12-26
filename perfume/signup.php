<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "perfume";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    } else {
        // Check if email already exists in signup_db
        $check_stmt = $conn->prepare("SELECT email FROM signup_db WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $errors[] = "Email already registered. Please use a different email or login.";
        }
        $check_stmt->close();
        
        // Also check users table to prevent duplicates
        $check_stmt2 = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check_stmt2->bind_param("s", $email);
        $check_stmt2->execute();
        $check_result2 = $check_stmt2->get_result();
        
        if ($check_result2->num_rows > 0) {
            $errors[] = "Email already registered. Please use a different email or login.";
        }
        $check_stmt2->close();
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, create account
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert into signup_db
        $stmt = $conn->prepare("INSERT INTO signup_db (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $success = true;
            
            // Also insert into users table for consistency
            $stmt2 = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, user_role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt2->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);
            $stmt2->execute();
            $stmt2->close();
            
            $stmt->close();
            $conn->close();
            
            // Redirect to login with success message
            header("Location: index.php?registered=success");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Scentrix Parfum</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #991304;
            --primary-dark: #7a0f03;
            --secondary-color: #000;
            --text-color: #333;
            --light-gray: #f9f9f9;
            --white: #fff;
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .form-container {
            background-color: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 500px;
            box-sizing: border-box;
            border-top: 5px solid var(--primary-color);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container h1 {
            font-size: 28px;
            color: var(--secondary-color);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .logo-container p {
            color: #666;
            font-size: 14px;
        }

        .form-container h2 {
            text-align: center;
            font-size: 24px;
            color: #333;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
        }

        .form-container h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            padding-left: 45px;
            border: 2px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-size: 16px;
            box-sizing: border-box;
            transition: var(--transition);
            background-color: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: var(--white);
            box-shadow: 0 0 0 3px rgba(153, 19, 4, 0.1);
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 42px;
            color: #666;
            font-size: 18px;
        }

        .form-group input:focus + i {
            color: var(--primary-color);
        }

        .form-container input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            border-radius: var(--border-radius);
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .form-container input[type="submit"]:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(153, 19, 4, 0.3);
        }

        .form-container p {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-top: 20px;
        }

        .form-container a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .form-container a:hover {
            text-decoration: underline;
            color: var(--primary-dark);
        }

        .error-message {
            color: #d32f2f;
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            font-size: 14px;
            border-left: 4px solid #d32f2f;
        }

        .error-message ul {
            margin: 0;
            padding-left: 20px;
        }

        .error-message li {
            margin-bottom: 5px;
        }

        .success-message {
            color: #388e3c;
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            font-size: 14px;
            border-left: 4px solid #388e3c;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 42px;
            cursor: pointer;
            color: #666;
            font-size: 18px;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .name-group {
            display: flex;
            gap: 15px;
        }

        .name-group .form-group {
            flex: 1;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .form-container {
                padding: 30px 20px;
            }

            .logo-container h1 {
                font-size: 24px;
            }

            .form-container h2 {
                font-size: 20px;
            }

            .form-group input {
                padding: 12px 15px;
                padding-left: 40px;
                font-size: 14px;
            }

            .form-group i {
                left: 12px;
                top: 37px;
                font-size: 16px;
            }

            .password-toggle {
                right: 12px;
                top: 37px;
                font-size: 16px;
            }

            .form-container input[type="submit"] {
                padding: 14px;
                font-size: 15px;
            }

            .name-group {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="logo-container">
            <h1><i class="fas fa-user-plus"></i> Create Account</h1>
            <p>Join Scentrix Parfum today</p>
        </div>
        
        <h2>Sign Up</h2>
        
        <?php
        // Display error messages
        if (!empty($errors)) {
            echo '<div class="error-message">';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        // Display success message
        if ($success) {
            echo '<div class="success-message"><i class="fas fa-check-circle"></i> Registration successful! Redirecting to login...</div>';
        }
        ?>
        
        <form method="POST" action="" id="signupForm">
            <div class="name-group">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <i class="fas fa-user"></i>
                    <input type="text" name="first_name" id="first_name" placeholder="Enter your first name" 
                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <i class="fas fa-user"></i>
                    <input type="text" name="last_name" id="last_name" placeholder="Enter your last name" 
                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="Enter your email address" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Create a password (min. 6 characters)" required>
                <span class="password-toggle" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
                <span class="password-toggle" id="toggleConfirmPassword">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            
            <input type="submit" value="Create Account">
        </form>
        
        <p>Already have an account? <a href="index.php">Login</a></p>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
        
        // Form validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const confirmPassword = document.getElementById('confirm_password').value.trim();
            
            if (!firstName || !lastName || !email || !password || !confirmPassword) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
            
            // Add loading state
            const submitBtn = this.querySelector('input[type="submit"]');
            submitBtn.value = 'Creating Account...';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
            
            return true;
        });
        
        // Auto-focus on first name field
        document.getElementById('first_name').focus();
    </script>

</body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>