<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $_SESSION['error'] = "Please enter your email.";
        header("Location: forgot-password.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: forgot-password.php");
        exit();
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $_SESSION['error'] = "Email not found in our records.";
        $stmt->close();
        header("Location: forgot-password.php");
        exit();
    }

    $stmt->close();

    // Here, you would generate a reset token and send an email with a reset link
    // For now, just simulate success:

    $_SESSION['success'] = "A password reset link has been sent to your email (simulation).";
    header("Location: forgot-password.php");
    exit();

} else {
    header("Location: forgot-password.php");
    exit();
}
?>
