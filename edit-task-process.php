<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority'];

    $errors = [];
    if (strlen($title) < 3) $errors[] = "Task title must be at least 3 characters.";
    if (strlen($description) < 10) $errors[] = "Description must be at least 10 characters.";
    if ($deadline < date('Y-m-d')) $errors[] = "Deadline cannot be in the past.";

    if (!empty($errors)) {
        $_SESSION['error'] = implode(" ", $errors);
        header("Location: edit-task.php?id=$id");
        exit();
    }

    $stmt = $conn->prepare("UPDATE tasks SET title=?, description=?, deadline=?, priority=? WHERE id=?");
    $stmt->bind_param("ssssi", $title, $description, $deadline, $priority, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Task updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating task.";
    }
    $stmt->close();
    header("Location: manage-tasks.php");
    exit();
}
?>
