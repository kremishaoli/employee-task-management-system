<?php
session_start();
include 'db.php';

// ✅ Only allow logged-in employees
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = intval($_POST['task_id']);
    $progress = intval($_POST['progress']);
    $status = $_POST['status'] ?? '';

    $valid_statuses = ['Not Started', 'In Progress', 'Completed'];
    if ($task_id <= 0 || $progress < 0 || $progress > 100 || !in_array($status, $valid_statuses)) {
        $_SESSION['error'] = "Invalid input values.";
        header("Location: update-progress.php");
        exit();
    }

    $employee_id = $_SESSION['user_id'];

    // ✅ Check task ownership
    $check = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_to = ?");
    $check->bind_param("ii", $task_id, $employee_id);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows === 0) {
        $_SESSION['error'] = "Unauthorized action.";
        header("Location: update-progress.php");
        exit();
    }

    try {
        $conn->begin_transaction();

        // ✅ Update tasks table
        $update = $conn->prepare("UPDATE tasks SET progress = ?, status = ? WHERE id = ?");
        $update->bind_param("isi", $progress, $status, $task_id);
        $update->execute();
        $update->close();

        // ✅ Update assigned_tasks table so status reflects current employee progress
        $stmt = $conn->prepare("UPDATE assigned_tasks SET status = ? WHERE task_id = ? AND employee_id = ?");
        $stmt->bind_param("sii", $status, $task_id, $employee_id);
        $stmt->execute();
        $stmt->close();

        // ✅ Log history in task_progress table
        $insertProgress = $conn->prepare("INSERT INTO task_progress (task_id, assigned_to, status, progress) VALUES (?, ?, ?, ?)");
        $insertProgress->bind_param("iisi", $task_id, $employee_id, $status, $progress);
        $insertProgress->execute();
        $insertProgress->close();

        $conn->commit();
        $_SESSION['success'] = "Progress and status updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to update progress: " . $e->getMessage();
    }

    header("Location: update-progress.php");
    exit();
} else {
    header("Location: update-progress.php");
    exit();
}
