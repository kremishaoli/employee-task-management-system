<?php
session_start();
include 'db.php';

// Check admin access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("Location: login.php");
    exit();
}

// Validate task ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid task ID.";
    header("Location: manage-tasks.php");
    exit();
}

$task_id = intval($_GET['id']);

// Delete the task
$stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Task deleted successfully.";
} else {
    $_SESSION['error'] = "Error deleting task. Please try again.";
}

$stmt->close();
header("Location: manage-tasks.php");
exit();
?>
