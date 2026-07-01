<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: create-task.php");
    exit();
}

// Sanitize inputs
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$deadline = $_POST['deadline'] ?? '';
$priority = $_POST['priority'] ?? '';
$assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;

// Validation
$errors = [];
if (strlen($title) < 3) $errors[] = "Task title must be at least 3 characters.";
if (strlen($description) < 10) $errors[] = "Description must be at least 10 characters.";
if (empty($deadline)) $errors[] = "Please select a deadline.";
elseif ($deadline < date('Y-m-d')) $errors[] = "Deadline cannot be in the past.";
if (empty($priority)) $errors[] = "Please select a priority.";

if ($errors) {
    $_SESSION['error'] = implode(" ", $errors);
    header("Location: create-task.php");
    exit();
}

// Insert task
$sql = "INSERT INTO tasks (title, description, deadline, priority";
$types = "ssss";
$params = [$title, $description, $deadline, $priority];

if ($assigned_to) {
    $sql .= ", assigned_to";
    $types .= "i";
    $params[] = $assigned_to;
}

$sql .= ") VALUES (?, ?, ?, ?";
if ($assigned_to) $sql .= ", ?";
$sql .= ")";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: create-task.php");
    exit();
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $_SESSION['success'] = "Task created successfully!";
} else {
    $_SESSION['error'] = "Error creating task: " . $stmt->error;
}

$stmt->close();
header("Location: create-task.php");
exit();
