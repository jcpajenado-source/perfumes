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

// Handle order status updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_status'])) {
        $order_id = intval($_POST['order_id']);
        $status = $conn->real_escape_string($_POST['status']);
        $admin_notes = isset($_POST['admin_notes']) ? $conn->real_escape_string($_POST['admin_notes']) : '';
        
        $stmt = $conn->prepare("UPDATE orders SET status = ?, admin_notes = ?, updated_at = NOW() WHERE order_id = ?");
        $stmt->bind_param("ssi", $status, $admin_notes, $order_id);
        
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Order #{$order_id} status updated to {$status}";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['admin_message'] = "Failed to update order status";
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
        
        header("Location: admin.php");
        exit();
    }
    
    if (isset($_POST['delete_order'])) {
        $order_id = intval($_POST['order_id']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete order items first
            $stmt1 = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt1->bind_param("i", $order_id);
            $stmt1->execute();
            $stmt1->close();
            
            // Delete order
            $stmt2 = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
            $stmt2->bind_param("i", $order_id);
            $stmt2->execute();
            $stmt2->close();
            
            $conn->commit();
            $_SESSION['admin_message'] = "Order #{$order_id} deleted successfully";
            $_SESSION['message_type'] = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['admin_message'] = "Failed to delete order";
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: admin.php");
        exit();
    }
}

// Fetch all orders with user information
$orders = [];
$sql = "SELECT o.*, u.first_name, u.last_name, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        ORDER BY o.order_date DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Fetch order items for each order
        $order_id = $row['order_id'];
        $items_sql = "SELECT oi.*, p.product_name, p.image 
                     FROM order_items oi 
                     LEFT JOIN products p ON oi.product_id = p.product_id 
                     WHERE oi.order_id = ?";
        $stmt = $conn->prepare($items_sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        $order_items = [];
        
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
        }
        $stmt->close();
        
        $row['items'] = $order_items;
        $orders[] = $row;
    }
}

// Statistics
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_orders,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(total_amount) as total_revenue
    FROM orders";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - Order Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-color);
            background-color: #f5f7fa;
            line-height: 1.6;
        }

        /* Navigation */
        .admin-nav {
            background-color: var(--secondary-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .admin-nav h1 {
            color: var(--white);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .admin-nav ul {
            list-style: none;
            display: flex;
            gap: 20px;
            align-items: center;
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

        /* Main Container */
        .admin-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Statistics Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 2rem;
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

        .stat-card.pending { border-top: 4px solid #ffc107; }
        .stat-card.approved { border-top: 4px solid #17a2b8; }
        .stat-card.shipped { border-top: 4px solid #28a745; }
        .stat-card.delivered { border-top: 4px solid var(--primary-color); }
        .stat-card.revenue { border-top: 4px solid #6f42c1; }

        /* Orders Table */
        .orders-section {
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

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .orders-table th {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--secondary-color);
            border-bottom: 2px solid #eee;
        }

        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .orders-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Order Details */
        .order-details {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-top: 20px;
            display: none;
        }

        .order-details.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .order-items {
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-right: 15px;
            border-radius: 5px;
            background: var(--white);
            padding: 5px;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .item-price {
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .btn-view {
            background-color: #17a2b8;
            color: white;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-status {
            background-color: #28a745;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            padding-top: 5%;
            overflow: auto;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: block;
            opacity: 1;
        }

        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(153, 19, 4, 0.1);
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }

        .close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            color: #aaa;
            cursor: pointer;
            transition: var(--transition);
        }

        .close:hover {
            color: #333;
        }

        /* Messages */
        .message-container {
            margin-bottom: 20px;
        }

        .success-message, .error-message {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Loading Spinner */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 3000;
            justify-content: center;
            align-items: center;
        }

        .loading.show {
            display: flex;
        }

        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .admin-nav ul {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .orders-section {
                padding: 20px;
            }
            
            .modal-content {
                width: 95%;
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
  </head>
  <body>
    <!-- Navigation -->
    <nav class="admin-nav">
        <h1><i class="fas fa-crown"></i> Admin Panel</h1>
        <ul>
            <li><a href="admin.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <div class="admin-container">
        <!-- Messages -->
        <div class="message-container">
            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="<?php echo $_SESSION['message_type'] === 'success' ? 'success-message' : 'error-message'; ?>">
                    <i class="fas fa-<?php echo $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($_SESSION['admin_message'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php unset($_SESSION['admin_message'], $_SESSION['message_type']); ?>
            <?php endif; ?>
        </div>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-number">₱<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_orders'] ?? 0; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $stats['pending_orders'] ?? 0; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            
            <div class="stat-card approved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['approved_orders'] ?? 0; ?></div>
                <div class="stat-label">Approved Orders</div>
            </div>
            
            <div class="stat-card shipped">
                <div class="stat-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <div class="stat-number"><?php echo $stats['shipped_orders'] ?? 0; ?></div>
                <div class="stat-label">Shipped Orders</div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="orders-section">
            <h2 class="section-title">Order Management</h2>
            
            <div class="table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; margin-bottom: 15px; display: block;"></i>
                                    <p>No orders found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                        <small><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></td>
                                    <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-view btn-sm" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-status btn-sm" onclick="openStatusModal(<?php echo $order['order_id']; ?>, '<?php echo $order['status']; ?>')">
                                                <i class="fas fa-edit"></i> Status
                                            </button>
                                            <form method="POST" action="" style="display: inline;" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <button type="submit" name="delete_order" class="btn btn-delete btn-sm">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Order Details Row -->
                                <tr id="order-details-<?php echo $order['order_id']; ?>" class="order-details-row">
                                    <td colspan="6">
                                        <div class="order-details" id="details-<?php echo $order['order_id']; ?>">
                                            <div class="order-info">
                                                <h4>Order Details</h4>
                                                <p><strong>Order ID:</strong> #<?php echo $order['order_id']; ?></p>
                                                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></p>
                                                <p><strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                                <p><strong>Order Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></p>
                                                <?php if ($order['updated_at']): ?>
                                                    <p><strong>Last Updated:</strong> <?php echo date('M d, Y h:i A', strtotime($order['updated_at'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($order['admin_notes']): ?>
                                                    <p><strong>Admin Notes:</strong> <?php echo htmlspecialchars($order['admin_notes']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="order-items">
                                                <h4>Order Items</h4>
                                                <?php foreach ($order['items'] as $item): ?>
                                                    <div class="order-item">
                                                        <img src="<?php echo htmlspecialchars($item['image'] ?? 'images/placeholder.jpg'); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                             class="item-image"
                                                             onerror="this.src='images/placeholder.jpg'">
                                                        <div class="item-info">
                                                            <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                            <div class="item-price">
                                                                ₱<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?>
                                                            </div>
                                                        </div>
                                                        <div class="item-total">
                                                            <strong>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <div class="order-total">
                                                <h4>Order Summary</h4>
                                                <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                                                    <span><strong>Total Amount:</strong></span>
                                                    <span><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></span>
                                                </div>
                                            </div>
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

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeStatusModal()">&times;</span>
            <h2 class="modal-title">Update Order Status</h2>
            <form id="statusForm" method="POST" action="">
                <input type="hidden" name="order_id" id="modalOrderId">
                <input type="hidden" name="update_status" value="1">
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="admin_notes">Admin Notes (Optional)</label>
                    <textarea name="admin_notes" id="admin_notes" placeholder="Add any notes about this order..."></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn btn-cancel" onclick="closeStatusModal()">Cancel</button>
                    <button type="submit" class="btn btn-status">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="loading" id="loadingSpinner">
        <div class="spinner"></div>
    </div>

    <script>
        // View order details
        function viewOrderDetails(orderId) {
            const detailsElement = document.getElementById(`details-${orderId}`);
            const rowElement = document.getElementById(`order-details-${orderId}`);
            
            // Toggle visibility
            if (detailsElement.classList.contains('active')) {
                detailsElement.classList.remove('active');
                rowElement.style.display = 'none';
            } else {
                // Close all other open details
                document.querySelectorAll('.order-details').forEach(detail => {
                    detail.classList.remove('active');
                });
                document.querySelectorAll('.order-details-row').forEach(row => {
                    row.style.display = 'none';
                });
                
                // Open this one
                detailsElement.classList.add('active');
                rowElement.style.display = 'table-row';
            }
        }

        // Open status update modal
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('status').value = currentStatus;
            document.getElementById('admin_notes').value = '';
            
            const modal = document.getElementById('statusModal');
            modal.classList.add('show');
            document.body.classList.add('modal-open');
        }

        // Close status modal
        function closeStatusModal() {
            const modal = document.getElementById('statusModal');
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
        }

        // Confirm delete
        function confirmDelete() {
            return confirm('Are you sure you want to delete this order? This action cannot be undone.');
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                closeStatusModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeStatusModal();
            }
        });

        // Handle form submission
        document.getElementById('statusForm')?.addEventListener('submit', function() {
            document.getElementById('loadingSpinner').classList.add('show');
        });

        // Hide loading spinner on page load
        window.addEventListener('load', function() {
            document.getElementById('loadingSpinner').classList.remove('show');
        });

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(msg => {
                msg.style.opacity = '0';
                msg.style.transition = 'opacity 0.5s ease';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
  </body>
</html>

<?php
// Close database connection
$conn->close();
?>