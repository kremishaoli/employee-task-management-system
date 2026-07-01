<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit();
}

// CSRF check
if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = "Invalid session token.";
    header("Location: register.php");
    exit();
}

// Sanitize & collect inputs
$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$role = 'employee';

// Server-side validation
if (!$fullname || !$email || !$username || !$password || !$confirmPassword) {
    $_SESSION['error'] = "Fill all fields.";
    header("Location: register.php");
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email.";
    header("Location: register.php");
    exit();
}
if (strlen($fullname) < 3 || strlen($username) < 3) {
    $_SESSION['error'] = "Name/Username too short.";
    header("Location: register.php");
    exit();
}
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/', $password)) {
    $_SESSION['error'] = "Password weak.";
    header("Location: register.php");
    exit();
}
if ($password !== $confirmPassword) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: register.php");
    exit();
}

// Check for existing username/email
$stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $_SESSION['error'] = "Username or Email exists.";
    $stmt->close();
    header("Location: register.php");
    exit();
}
$stmt->close();

// Hash password & insert
$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (fullname,email,username,password,role) VALUES (?,?,?,?,?)");
$stmt->bind_param("sssss", $fullname, $email, $username, $hashed, $role);

if ($stmt->execute()) {
    unset($_SESSION['csrf_token']);
    $_SESSION['success'] = "Registered successfully! Login now.";
    header("Location: login.php");
    exit();
} else {
    $_SESSION['error'] = "Registration failed.";
    header("Location: register.php");
    exit();
}
