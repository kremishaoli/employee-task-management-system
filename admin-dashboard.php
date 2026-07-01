<?php
session_start();

// Only allow admin or super_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header("Location: login.php"); // redirect to new unified login
    exit();
}

include 'db.php';
$today = date('Y-m-d');

// Fetch task statistics
$totalTasks = $conn->query("SELECT COUNT(*) AS total FROM tasks")->fetch_assoc()['total'] ?? 0;
$completedTasks = $conn->query("SELECT COUNT(*) AS completed FROM tasks WHERE status = 'Completed'")->fetch_assoc()['completed'] ?? 0;
$overdueTasks = $conn->query("SELECT COUNT(*) AS overdue FROM tasks WHERE deadline < '$today' AND status != 'Completed'")->fetch_assoc()['overdue'] ?? 0;
$pendingTasks = $totalTasks - $completedTasks;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - ETMS</title>
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
    <h2>Welcome, <?= htmlspecialchars($_SESSION['fullname']); ?> (<?= htmlspecialchars($_SESSION['role']); ?>)</h2>
  </header>

  <!-- Task Summary -->
  <section class="dashboard-overview">
    <h3>Task Summary</h3>
    <div class="card-grid">
      <div class="card">
        <h4>Total Tasks</h4>
        <p><?= $totalTasks; ?></p>
      </div>
      <div class="card">
        <h4>Completed</h4>
        <p><?= $completedTasks; ?></p>
      </div>
      <div class="card">
        <h4>Pending</h4>
        <p><?= $pendingTasks; ?></p>
      </div>
      <div class="card <?= $overdueTasks > 0 ? 'overdue-highlight' : '' ?>">
        <h4>Overdue</h4>
        <p><?= $overdueTasks > 0 ? $overdueTasks : '0'; ?></p>
      </div>
    </div>
  </section>

  <!-- Dashboard Overview -->
  <section class="dashboard-overview">
    <h3>Quick Actions</h3>
    <div class="card-grid">
      <?php
      $cards = [
        ['title'=>'Create Task', 'desc'=>'Add new tasks with deadlines and priorities.', 'link'=>'create-task.php'],
        ['title'=>'Manage Users', 'desc'=>'Add, edit, or remove employees and admins.', 'link'=>'manage-users.php'],
        ['title'=>'Assign Tasks', 'desc'=>'Assign tasks to employees easily.', 'link'=>'assign-task.php'],
        ['title'=>'Monitor Progress', 'desc'=>'Track all tasks and their progress in real time.', 'link'=>'monitor-progress.php'],
        ['title'=>'Evaluate Employees', 'desc'=>'Review and rate employee performance.', 'link'=>'evaluate-employees.php'],
        ['title'=>'Manage Tasks', 'desc'=>'Edit or delete tasks if needed.', 'link'=>'manage-tasks.php'],
        ['title'=>'Reports', 'desc'=>'Generate detailed reports for analysis.', 'link'=>'reports.php']
      ];

      foreach ($cards as $card): ?>
        <div class="card">
          <h4><?= $card['title']; ?></h4>
          <p><?= $card['desc']; ?></p>
          <a href="<?= $card['link']; ?>" class="btn">Go</a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

</body>
</html>
