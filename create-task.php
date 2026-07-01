<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("Location: login.php");
    exit();
}
$selfRole = $_SESSION['role'];

// Fetch employees for optional assignment dropdown
$employees = [];
$result = $conn->query("SELECT id, fullname FROM users WHERE role='employee' ORDER BY fullname ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Task - ETMS</title>
<link rel="stylesheet" href="dashboard.css">
</head>
<body>

<div class="sidebar">
  <div class="sidebar-logo">ETMS</div>
  <ul class="sidebar-menu">
    <li><a href="admin-dashboard.php">Dashboard</a></li>
    <li><a href="manage-users.php">Manage Users</a></li>
    <li><a href="create-task.php" class="active">Create Task</a></li>
    <li><a href="manage-tasks.php">Manage Tasks</a></li>
    <li><a href="assign-task.php">Assign Tasks</a></li>
    <li><a href="monitor-progress.php">Monitor Progress</a></li>
    <li><a href="evaluate-employees.php">Evaluate Employees</a></li>
    <li><a href="reports.php">Reports</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <header><h2>Create New Task</h2></header>

  <?php if (!empty($_SESSION['success'])): ?>
    <p class="success-msg"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
  <?php endif; ?>
  <?php if (!empty($_SESSION['error'])): ?>
    <p class="error-msg"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
  <?php endif; ?>

  <form id="taskForm" action="create-task-process.php" method="POST" novalidate>
    <label for="title">Task Title</label>
    <input type="text" id="title" name="title" placeholder="Enter task title" required minlength="3">

    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4" placeholder="Enter task description" required minlength="10"></textarea>

    <label for="deadline">Deadline</label>
    <input type="date" id="deadline" name="deadline" required min="<?= date('Y-m-d') ?>">

    <label for="priority">Priority</label>
    <select id="priority" name="priority" required>
      <option value="" disabled selected>Select priority</option>
      <option value="Low">Low</option>
      <option value="Medium">Medium</option>
      <option value="High">High</option>
    </select>

    <?php if (!empty($employees)): ?>
      <label for="assigned_to">Assign to Employee (optional)</label>
      <select name="assigned_to" id="assigned_to">
        <option value="" selected>-- None --</option>
        <?php foreach($employees as $emp): ?>
          <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['fullname']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <button type="submit" class="btn">Create Task</button>
  </form>
</div>

<script>
document.getElementById('taskForm').addEventListener('submit', function(e) {
    const errors = [];
    const title = this.title.value.trim();
    const description = this.description.value.trim();
    const deadline = this.deadline.value;
    const priority = this.priority.value;

    if (title.length < 3) errors.push("Task title must be at least 3 characters.");
    if (description.length < 10) errors.push("Description must be at least 10 characters.");
    if (!deadline) errors.push("Please select a deadline.");
    else if (deadline < new Date().toISOString().split('T')[0]) errors.push("Deadline cannot be in the past.");
    if (!priority) errors.push("Please select a priority.");

    if (errors.length) {
        alert(errors.join("\n"));
        e.preventDefault();
    } else {
        this.querySelector('button[type="submit"]').disabled = true;
    }
});
</script>
</body>
</html>
