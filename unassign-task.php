<?php
session_start();
include 'db.php';

// Only admin and super_admin
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

try {
    $conn->begin_transaction();

    // Fetch current assignment
    $stmt = $conn->prepare("SELECT assigned_to FROM tasks WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->bind_result($assigned_to);
    $stmt->fetch();
    $stmt->close();

    if (!$assigned_to) {
        throw new Exception("This task is not assigned to anyone.");
    }

    // Update tasks table (current assignment)
    $stmt = $conn->prepare("UPDATE tasks SET assigned_to = NULL WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->close();

    // Update assigned_tasks table (history)
    $stmt = $conn->prepare("UPDATE assigned_tasks SET unassigned_at = NOW() WHERE task_id = ? AND employee_id = ? AND unassigned_at IS NULL");
    $stmt->bind_param("ii", $task_id, $assigned_to);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $_SESSION['success'] = "Task unassigned successfully.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Failed to unassign task: " . $e->getMessage();
}

header("Location: manage-tasks.php");
exit();
?>
