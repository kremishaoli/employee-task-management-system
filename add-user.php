<?php
session_start();
include 'db.php';

// Only super_admin can add users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    $_SESSION['error'] = "You do not have permission to add users.";
    header("Location: manage-users.php");
    exit();
}

// Check CSRF
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Invalid CSRF token.";
    header("Location: manage-users.php");
    exit();
}

// Get and sanitize inputs
$fullname = trim($_POST['fullname'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$role = $_POST['role'] ?? 'employee';

// Validate inputs
$errors = [];
if (strlen($fullname) < 3) $errors[] = "Full name must be at least 3 characters.";
if (strlen($username) < 3) $errors[] = "Username must be at least 3 characters.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
if ($password !== $confirmPassword) $errors[] = "Passwords do not match.";
if (!in_array($role, ['employee','admin'])) $role = 'employee';

if ($errors) {
    $_SESSION['error'] = implode(" ", $errors);
    header("Location: manage-users.php");
    exit();
}

// Check for existing username/email
$stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $_SESSION['error'] = "Username or email already exists.";
    $stmt->close();
    header("Location: manage-users.php");
    exit();
}
$stmt->close();

// Hash password and insert user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $fullname, $username, $email, $hashedPassword, $role);

if ($stmt->execute()) {
    $_SESSION['success'] = "New user created successfully.";
} else {
    $_SESSION['error'] = "Failed to create user: " . $stmt->error;
}

$stmt->close();
header("Location: manage-users.php");
exit();
