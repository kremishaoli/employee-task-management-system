<?php
session_start();
include 'db.php';

// Check if user is admin
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

// Fetch existing task details
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Task not found.";
    header("Location: manage-tasks.php");
    exit();
}

$task = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $progress = intval($_POST['progress']);

    // Validation
    if (empty($title) || empty($description) || empty($deadline) || empty($priority) || empty($status)) {
        $_SESSION['error'] = "Please fill in all fields.";
        header("Location: edit-task.php?id=" . $task_id);
        exit();
    }

    if (!in_array($priority, ['Low', 'Medium', 'High'])) {
        $_SESSION['error'] = "Invalid priority.";
        header("Location: edit-task.php?id=" . $task_id);
        exit();
    }

    if (!in_array($status, ['Not Started', 'In Progress', 'Completed'])) {
        $_SESSION['error'] = "Invalid status.";
        header("Location: edit-task.php?id=" . $task_id);
        exit();
    }

    if ($progress < 0 || $progress > 100) {
        $_SESSION['error'] = "Progress must be between 0 and 100.";
        header("Location: edit-task.php?id=" . $task_id);
        exit();
    }

    // Check if deadline is past
    $today = date('Y-m-d');
    if ($deadline < $today) {
        $_SESSION['error'] = "Deadline cannot be in the past.";
        header("Location: edit-task.php?id=" . $task_id);
        exit();
    }

    // Update task in database
    $stmt = $conn->prepare("UPDATE tasks SET title=?, description=?, deadline=?, priority=?, status=?, progress=? WHERE id=?");
    $stmt->bind_param("ssssssi", $title, $description, $deadline, $priority, $status, $progress, $task_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Task updated successfully!";
        header("Location: manage-tasks.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating task. Please try again.";
        header("Location: edit-task.php?id=" . $task_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Task - ETMS</title>
  <link rel="stylesheet" href="dashboard.css" />
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-logo">ETMS</div>
  <ul class="sidebar-menu">
    <li><a href="admin-dashboard.php" class="active">Dashboard</a></li>
    <li><a href="manage-users.php">Manage Users</a></li>
    <li><a href="create-task.php">Create Task</a></li>
    <li><a href="manage-tasks.php">Manage Tasks</a></li>
    <li><a href="assign-task.php">Assign Tasks</a></li>
    <li><a href="monitor-progress.php">Monitor Progress</a></li>
    <li><a href="evaluate-employees.php">Evaluate Employees</a></li>
    <li><a href="reports.php">Reports</a></li>
    <li><a href="logout.php" class="logout">Logout</a></li>
  </ul>
</div>

<!-- Main Content -->
<div class="main-content">
  <header>
    <h2>Edit Task</h2>
  </header>

  <section class="form-section">
    <?php if (isset($_SESSION['error'])): ?>
      <p class="error-msg"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <form action="" method="POST" class="task-form">
      <label for="title">Task Title</label>
      <input type="text" id="title" name="title" value="<?= htmlspecialchars($task['title']); ?>" required />

      <label for="description">Description</label>
      <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($task['description']); ?></textarea>

      <label for="deadline">Deadline</label>
      <input type="date" id="deadline" name="deadline" value="<?= htmlspecialchars($task['deadline']); ?>" required />

      <label for="priority">Priority</label>
      <select id="priority" name="priority" required>
        <option value="Low" <?= $task['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
        <option value="Medium" <?= $task['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
        <option value="High" <?= $task['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
      </select>

      <label for="status">Status</label>
      <select id="status" name="status" required>
        <option value="Not Started" <?= $task['status'] === 'Not Started' ? 'selected' : ''; ?>>Not Started</option>
        <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
        <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
      </select>

      <label for="progress">Progress (%)</label>
      <input type="number" id="progress" name="progress" value="<?= htmlspecialchars($task['progress']); ?>" min="0" max="100" required />

      <button type="submit" class="btn">Update Task</button>
    </form>
  </section>
</div>
</body>
</html>
