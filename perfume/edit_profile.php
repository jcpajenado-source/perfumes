<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = [];

// Get user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    $update_sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ssssi", $first_name, $last_name, $phone, $address, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = 'Profile updated successfully!';
        header("Location: myprofile.php");
        exit();
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <style>
        /* Add your styles here */
    </style>
</head>
<body>
    <h1>Edit Profile</h1>
    <form method="POST">
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>">
        <textarea name="address"><?php echo htmlspecialchars($user_data['address']); ?></textarea>
        <button type="submit">Save Changes</button>
    </form>
</body>
</html>