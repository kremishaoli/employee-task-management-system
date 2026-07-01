<?php
session_start();
include 'db.php';

// Only admin and super_admin can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("Location: admin-login.php");
    exit();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = $_POST['csrf_token'] ?? '';
    if ($csrf !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: evaluate-employees.php");
        exit();
    }

    $employee_id = intval($_POST['employee_id']);
    $task_id = intval($_POST['task_id']);
    $rating = intval($_POST['rating']);
    $comments = trim($_POST['comments']);
    $evaluated_by = $_SESSION['user_id'];

    // Suggested rating from system
    $suggested_rating = isset($_POST['suggested_rating']) ? intval($_POST['suggested_rating']) : null;

    // Basic validation
    if (!$employee_id || !$task_id || !is_numeric($rating) || $rating < 0 || $rating > 10) {
        $_SESSION['error'] = "Invalid input. Please check all fields.";
        header("Location: evaluate-employees.php");
        exit();
    }

    // Check if employee exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id=? AND role='employee'");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Selected employee does not exist.";
        $stmt->close();
        header("Location: evaluate-employees.php");
        exit();
    }
    $stmt->close();

    // Check if task exists
    $stmt = $conn->prepare("SELECT id FROM tasks WHERE id=?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Selected task does not exist.";
        $stmt->close();
        header("Location: evaluate-employees.php");
        exit();
    }
    $stmt->close();

    // ✅ Insert evaluation with correct bind_param types
    $stmt = $conn->prepare("
        INSERT INTO evaluations 
        (employee_id, task_id, rating, suggested_rating, comments, evaluated_by, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    // i = integer, s = string
    $stmt->bind_param("iiiisi", $employee_id, $task_id, $rating, $suggested_rating, $comments, $evaluated_by);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Evaluation submitted successfully!";
    } else {
        $_SESSION['error'] = "Error submitting evaluation. Please try again.";
    }

    $stmt->close();

    // Redirect back to the same page so user sees the message
    header("Location: evaluate-employees.php");
    exit();

} else {
    // Redirect if accessed directly
    header("Location: evaluate-employees.php");
    exit();
}
?>
