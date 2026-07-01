<?php
session_start();
include 'db.php';

// Only admin or super_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage-tasks.php");
    exit();
}

$task_id = intval($_POST['task_id'] ?? 0);
$employee_id = intval($_POST['employee_id'] ?? -1);

if ($task_id <= 0 || $employee_id < 0) {
    $_SESSION['error'] = "Invalid task or employee selection.";
    header("Location: assign-task.php?task_id=$task_id");
    exit();
}

try {
    $conn->begin_transaction();

    // Fetch current task info
    $stmt = $conn->prepare("SELECT status, assigned_to FROM tasks WHERE id=? FOR UPDATE");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->bind_result($status, $current_assigned_to);
    if (!$stmt->fetch()) throw new Exception("Task not found.");
    $stmt->close();

    // Cannot assign completed tasks
    if ($status === 'Completed') throw new Exception("Cannot assign a completed task.");

    // Determine new employee (NULL if unassigned)
    $new_employee_id = ($employee_id === 0) ? null : $employee_id;

    // Validate employee exists
    if ($new_employee_id !== null) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id=? AND role='employee'");
        $stmt->bind_param("i", $new_employee_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) throw new Exception("Employee not found.");
        $stmt->close();
    }

    // Update tasks table
    $stmt = $conn->prepare("UPDATE tasks SET assigned_to=? WHERE id=?");
    $stmt->bind_param("ii", $new_employee_id, $task_id);
    $stmt->execute();
    $stmt->close();

    // Insert or update assigned_tasks table
    if ($new_employee_id !== null) {
        // If already assigned, update assigned_date & status
        $stmt = $conn->prepare("
            INSERT INTO assigned_tasks (task_id, employee_id, assigned_date, status, assigned_at)
            VALUES (?, ?, CURDATE(), 'Not Started', NOW())
            ON DUPLICATE KEY UPDATE employee_id=VALUES(employee_id), assigned_date=CURDATE(), status='Not Started', assigned_at=NOW()
        ");
        $stmt->bind_param("ii", $task_id, $new_employee_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // If unassigned, delete from assigned_tasks
        $stmt = $conn->prepare("DELETE FROM assigned_tasks WHERE task_id=?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    $_SESSION['success'] = $new_employee_id ? "Task assigned/reassigned successfully!" : "Task unassigned successfully!";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Failed to assign task: " . $e->getMessage();
}

header("Location: manage-tasks.php");
exit();
