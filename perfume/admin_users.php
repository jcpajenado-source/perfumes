<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "perfume";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle user actions
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update user role
    if (isset($_POST['update_role'])) {
        $user_id = intval($_POST['user_id']);
        $user_role = $conn->real_escape_string($_POST['user_role']);
        
        $stmt = $conn->prepare("UPDATE users SET user_role = ? WHERE user_id = ?");
        $stmt->bind_param("si", $user_role, $user_id);
        
        if ($stmt->execute()) {
            $message = "User role updated successfully!";
            $message_type = "success";
        } else {
            $message = "Failed to update user role: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
    }
    
    // Delete user
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First, delete user's orders and order items
            $stmt1 = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ?");
            $stmt1->bind_param("i", $user_id);
            $stmt1->execute();
            $orders_result = $stmt1->get_result();
            
            while ($order = $orders_result->fetch_assoc()) {
                $delete_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
                $delete_items->bind_param("i", $order['order_id']);
                $delete_items->execute();
                $delete_items->close();
            }
            $stmt1->close();
            
            // Delete user's orders
            $stmt2 = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            $stmt2->close();
            
            // Delete user from users table
            $stmt3 = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt3->bind_param("i", $user_id);
            $stmt3->execute();
            $stmt3->close();
            
            // Also delete from signup_db if exists
            $stmt4 = $conn->prepare("DELETE FROM signup_db WHERE email = (SELECT email FROM users WHERE user_id = ?)");
            $stmt4->bind_param("i", $user_id);
            $stmt4->execute();
            $stmt4->close();
            
            $conn->commit();
            $message = "User deleted successfully!";
            $message_type = "success";
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Failed to delete user: " . $e->getMessage();
            $message_type = "error";
        }
    }
    
    // Add new user (admin only)
    if (isset($_POST['add_user'])) {
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'];
        $user_role = $conn->real_escape_string($_POST['user_role']);
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        $address = $conn->real_escape_string($_POST['address'] ?? '');
        
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $message = "Email already exists!";
            $message_type = "error";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, phone, address, user_role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $first_name, $last_name, $email, $hashed_password, $phone, $address, $user_role);
            
            if ($stmt->execute()) {
                $message = "User added successfully!";
                $message_type = "success";
            } else {
                $message = "Failed to add user: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// Fetch all users with order statistics
$users = [];
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM orders WHERE user_id = u.user_id) as total_orders,
        (SELECT SUM(total_amount) FROM orders WHERE user_id = u.user_id) as total_spent,
        (SELECT MAX(order_date) FROM orders WHERE user_id = u.user_id) as last_order_date
        FROM users u 
        ORDER BY u.created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get user statistics
$stats_sql = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN user_role = 'admin' THEN 1 ELSE 0 END) as admin_users,
    SUM(CASE WHEN user_role = 'customer' THEN 1 ELSE 0 END) as customer_users,
    AVG((SELECT COUNT(*) FROM orders WHERE user_id = users.user_id)) as avg_orders_per_user,
    AVG((SELECT SUM(total_amount) FROM orders WHERE user_id = users.user_id)) as avg_spent_per_user
    FROM users";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Close connection after operations
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #991304;
            --primary-dark: #7a0f03;
            --secondary-color: #2c3e50;
            --text-color: #333;
            --light-gray: #f9f9f9;
            --white: #fff;
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--text-color);
        }

        .admin-nav {
            background-color: var(--secondary-color);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .admin-nav h1 {
            color: var(--white);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .admin-nav ul {
            list-style: none;
            display: flex;
            gap: 20px;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        .admin-nav a {
            color: var(--white);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .admin-nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .logout-btn {
            background-color: var(--primary-color);
            color: white !important;
        }

        .logout-btn:hover {
            background-color: var(--primary-dark) !important;
        }

        .container-fluid {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Statistics Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            border-top: 4px solid var(--primary-color);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Users Table */
        .users-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--secondary-color);
            border-bottom: 2px solid #eee;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Badges */
        .role-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-admin {
            background-color: #d4edda;
            color: #155724;
        }

        .role-customer {
            background-color: #cce5ff;
            color: #004085;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-add {
            background-color: var(--primary-color);
            color: white;
            float: right;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* Messages */
        .message-container {
            margin-bottom: 20px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Modal */
        .modal-header {
            background-color: var(--secondary-color);
            color: white;
        }

        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
        }

        /* User Avatar */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-nav {
                padding: 15px;
            }
            
            .admin-nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .users-section {
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .table {
                font-size: 0.9rem;
            }
            
            .table td, .table th {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="admin-nav navbar navbar-expand-lg">
        <div class="container-fluid">
            <h1><i class="fas fa-crown"></i> Admin Panel - Users</h1>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="admin.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
                <li class="nav-item"><a class="nav-link active" href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li class="nav-item"><a class="nav-link logout-btn" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container-fluid">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_users'] ?? 0; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-number"><?php echo $stats['admin_users'] ?? 0; ?></div>
                <div class="stat-label">Admin Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stat-number"><?php echo $stats['customer_users'] ?? 0; ?></div>
                <div class="stat-label">Customers</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['avg_orders_per_user'] ?? 0, 1); ?></div>
                <div class="stat-label">Avg Orders/User</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-number">₱<?php echo number_format($stats['avg_spent_per_user'] ?? 0, 2); ?></div>
                <div class="stat-label">Avg Spent/User</div>
            </div>
        </div>

        <!-- Users Management -->
        <div class="users-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title">User Management</h2>
                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i> Add New User
                </button>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Total Orders</th>
                            <th>Total Spent</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-users" style="font-size: 3rem; color: #ddd; margin-bottom: 15px; display: block;"></i>
                                    <p>No users found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                <small class="d-block text-muted">ID: #<?php echo $user['user_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['user_role']; ?>">
                                            <?php echo ucfirst($user['user_role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['total_orders'] ?? 0; ?></td>
                                    <td>
                                        <strong>₱<?php echo number_format($user['total_spent'] ?? 0, 2); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        <small class="d-block text-muted">
                                            <?php 
                                            if ($user['last_order_date']) {
                                                echo 'Last order: ' . date('M d', strtotime($user['last_order_date']));
                                            } else {
                                                echo 'No orders yet';
                                            }
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-edit btn-sm" 
                                                    onclick="editUser(<?php echo $user['user_id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their orders.')">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-delete btn-sm">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm" method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_role" class="form-label">Role *</label>
                                    <select class="form-control" id="user_role" name="user_role" required>
                                        <option value="customer">Customer</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm" method="POST" action="">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="edit_first_name" name="first_name" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="edit_last_name" name="last_name" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="edit_email" name="email" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_user_role" class="form-label">Role *</label>
                            <select class="form-control" id="edit_user_role" name="user_role" required>
                                <option value="customer">Customer</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <p class="text-muted">To change password, ask user to use "Forgot Password" feature.</p>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_role" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit user function
        function editUser(userId) {
            // Fetch user details via AJAX
            fetch(`get_user.php?id=${userId}`)
                .then(response => response.json())
                .then(user => {
                    document.getElementById('edit_user_id').value = user.user_id;
                    document.getElementById('edit_first_name').value = user.first_name;
                    document.getElementById('edit_last_name').value = user.last_name;
                    document.getElementById('edit_email').value = user.email;
                    document.getElementById('edit_user_role').value = user.user_role;
                    
                    // Show modal
                    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                    editModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load user details');
                });
        }

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Form validation
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return false;
            }
            
            return true;
        });

        // Email format validation
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.classList.add('is-invalid');
                this.nextElementSibling?.classList?.remove('d-none');
            } else {
                this.classList.remove('is-invalid');
                this.nextElementSibling?.classList?.add('d-none');
            }
        });

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('passwordStrength');
            
            if (!strengthIndicator) {
                const indicator = document.createElement('div');
                indicator.id = 'passwordStrength';
                indicator.className = 'mt-1';
                this.parentNode.appendChild(indicator);
            }
            
            let strength = 'Weak';
            let color = '#dc3545';
            
            if (password.length >= 8) {
                strength = 'Medium';
                color = '#ffc107';
            }
            
            if (password.length >= 10 && /[A-Z]/.test(password) && /[0-9]/.test(password)) {
                strength = 'Strong';
                color = '#28a745';
            }
            
            document.getElementById('passwordStrength').innerHTML = 
                `<small>Strength: <span style="color: ${color}">${strength}</span></small>`;
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>