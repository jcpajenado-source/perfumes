<?php
require "db.php";

$first = trim($_POST["first_name"] ?? "");
$last  = trim($_POST["last_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$pass  = $_POST["password"] ?? "";
$confirm = $_POST["confirm_password"] ?? "";

if ($first === "" || $last === "" || $email === "" || $pass === "" || $confirm === "") {
  die("All fields are required.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  die("Invalid email.");
}

if ($pass !== $confirm) {
  die("Passwords do not match.");
}

if (strlen($pass) < 6) {
  die("Password must be at least 6 characters.");
}

$password_hash = password_hash($pass, PASSWORD_DEFAULT);

/* check if email exists */
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  die("Email already registered.");
}
$check->close();

/* insert user */
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $first, $last, $email, $password_hash);

if ($stmt->execute()) {
  // redirect after signup
  header("Location: log in.html");
  exit;
} else {
  echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();