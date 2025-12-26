<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'No order ID provided']);
    exit();
}

// Check if order belongs to user and is cancellable
$sql = "UPDATE purchases SET status = 'cancelled', cancelled_at = NOW() 
        WHERE id = ? AND user_id = ? AND status = 'pending'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Unable to cancel order']);
}

mysqli_close($conn);
?>