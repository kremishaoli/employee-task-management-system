<?php
session_start();
include 'db.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

// Get POST data
$usernameOrEmail = trim($_POST['usernameOrEmail'] ?? '');
$password        = $_POST['password'] ?? '';
$csrfToken       = $_POST['csrf_token'] ?? '';

// CSRF check
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    $_SESSION['error'] = "Invalid session token. Please try again.";
    header("Location: login.php");
    exit();
}

// Input validation
if ($usernameOrEmail === '' || $password === '') {
    $_SESSION['error'] = "Please fill in all fields.";
    header("Location: login.php");
    exit();
}

// Fetch user
$stmt = $conn->prepare(
    "SELECT id, fullname, username, email, password, role
     FROM users
     WHERE username = ? OR email = ?
     LIMIT 1"
);
$stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Verify password
if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);

    $_SESSION['user_id']  = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['role']     = $user['role'];

    unset($_SESSION['csrf_token']); // rotate CSRF token

    // Redirect by role
    if ($user['role'] === 'super_admin' || $user['role'] === 'admin') {
        header("Location: admin-dashboard.php");
    } else {
        header("Location: employee-dashboard.php");
    }
    exit();
}

// Login failed
$_SESSION['error'] = "Invalid username/email or password.";
header("Location: login.php");
exit();
