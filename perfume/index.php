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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no errors, process login with database
    if (empty($errors)) {
        // Check if it's admin login (from users table)
        if ($email === 'admin@gmail.com') {
            // Check in users table for admin
            $stmt = $conn->prepare("SELECT user_id, email, password, first_name, last_name, user_role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // DEBUG: Check what's happening
                error_log("Admin login attempt: " . $email);
                error_log("Provided password: " . $password);
                error_log("Stored hash: " . $user['password']);
                
                // Verify password for admin
                if (password_verify($password, $user['password'])) {
                    // Set session variables for admin
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['user_role'] = $user['user_role'];
                    $_SESSION['logged_in'] = true;
                    
                    $stmt->close();
                    $conn->close();
                    
                    // Redirect to admin.php
                    header("Location: admin.php");
                    exit();
                } else {
                    // Special case: If password is "admin123" but hash doesn't match, update it
                    if ($password === "admin123") {
                        // Generate new hash for admin123
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                        $update_stmt->bind_param("ss", $new_hash, $email);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // Set session variables for admin
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['user_role'] = $user['user_role'];
                        $_SESSION['logged_in'] = true;
                        
                        $stmt->close();
                        $conn->close();
                        
                        // Redirect to admin.php
                        header("Location: admin.php");
                        exit();
                    } else {
                        $errors[] = "Invalid email or password";
                    }
                }
            } else {
                // Admin account doesn't exist, create it
                $admin_password = "admin123";
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                
                $insert_stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, phone, address, user_role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("sssssss", 
                    "Admin", 
                    "User", 
                    "admin@gmail.com", 
                    $hashed_password,
                    "+639123456789",
                    "123 Admin Street",
                    "admin"
                );
                
                if ($insert_stmt->execute()) {
                    $admin_id = $insert_stmt->insert_id;
                    
                    // Set session variables for newly created admin
                    $_SESSION['user_id'] = $admin_id;
                    $_SESSION['user_email'] = "admin@gmail.com";
                    $_SESSION['first_name'] = "Admin";
                    $_SESSION['last_name'] = "User";
                    $_SESSION['user_role'] = "admin";
                    $_SESSION['logged_in'] = true;
                    
                    $insert_stmt->close();
                    $conn->close();
                    
                    // Redirect to admin.php
                    header("Location: admin.php");
                    exit();
                } else {
                    $errors[] = "Error creating admin account: " . $conn->error;
                }
            }
            $stmt->close();
        } else {
            // Regular user login (from signup_db)
            $stmt = $conn->prepare("SELECT user_id, email, password_hash, first_name FROM signup_db WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['user_role'] = 'customer'; // Default role for signup_db users
                    $_SESSION['logged_in'] = true;
                    
                    $stmt->close();
                    $conn->close();
                    
                    // Redirect to homepage
                    header("Location: homepage.php");
                    exit();
                } else {
                    $errors[] = "Invalid email or password";
                }
            } else {
                $errors[] = "Invalid email or password";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Scentrix Parfum</title>
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
            max-width: 450px;
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

        .admin-note {
            text-align: center;
            margin-top: 25px;
            padding: 15px;
            background-color: #f0f8ff;
            border-radius: var(--border-radius);
            border-left: 4px solid #4a90e2;
            font-size: 13px;
        }

        .admin-note p {
            margin: 5px 0;
            color: #2c5282;
        }

        .admin-note strong {
            color: var(--primary-color);
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
        }

        @media (max-width: 400px) {
            .form-container {
                padding: 25px 15px;
            }

            .logo-container h1 {
                font-size: 22px;
            }

            .form-container h2 {
                font-size: 18px;
            }

            .form-group input {
                padding: 10px 12px;
                padding-left: 35px;
            }

            .form-group i {
                left: 10px;
                top: 35px;
            }

            .password-toggle {
                right: 10px;
                top: 35px;
            }
        }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="logo-container">
            <h1><i class="fas fa-crown"></i> Scentrix Parfum</h1>
            <p>Login to your account</p>
        </div>
        
        <h2>Sign In</h2>
        
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
        
        // Display logout message if redirected from logout
        if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
            echo '<div class="success-message"><i class="fas fa-check-circle"></i> You have been successfully logged out.</div>';
        }
        
        // Display registration success message if redirected from signup
        if (isset($_GET['registered']) && $_GET['registered'] == 'success') {
            echo '<div class="success-message"><i class="fas fa-check-circle"></i> Registration successful! Please login with your credentials.</div>';
        }
        ?>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="Enter your email address" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>
                <span class="password-toggle" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            
            <input type="submit" value="Login">
        </form>
        
        <p>Don't have an account? <a href="signup.php">Sign up</a></p>
        
        <div class="admin-note">
            <p><i class="fas fa-user-shield"></i> <strong>Admin Login:</strong></p>
            <p>Email: admin@gmail.com | Password: admin123</p>
            <p><small>If admin account doesn't exist, it will be created automatically</small></p>
        </div>
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
        
        // Auto-focus on email field
        document.getElementById('email').focus();
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Add loading state
            const submitBtn = this.querySelector('input[type="submit"]');
            submitBtn.value = 'Logging in...';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
            
            return true;
        });
        
        // Remove loading state if page is reloaded
        window.addEventListener('pageshow', function() {
            const submitBtn = document.querySelector('input[type="submit"]');
            if (submitBtn) {
                submitBtn.value = 'Login';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
        });
    </script>

</body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>