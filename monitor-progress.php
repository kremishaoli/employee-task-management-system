<?php
session_start();
include 'db.php';

// Only admins and super_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header("Location: login.php");
    exit();
}

// GET parameters
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$tasksPerPage = 10;
$offset = ($page - 1) * $tasksPerPage;
$sort = $_GET['sort'] ?? 'deadline';

// Safe ORDER BY mapping
$allowedSort = [
    'deadline' => 'tasks.deadline ASC',
    'priority' => 'tasks.priority ASC',
    'progress' => 'tasks.progress DESC'
];
$orderBy = $allowedSort[$sort] ?? $allowedSort['deadline'];

// Count total tasks
if ($search !== '') {
    $countSQL = "SELECT COUNT(*) FROM tasks 
                 LEFT JOIN users ON tasks.assigned_to = users.id
                 WHERE tasks.title LIKE ? OR users.fullname LIKE ?";
    $stmt = $conn->prepare($countSQL);
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
} else {
    $countSQL = "SELECT COUNT(*) FROM tasks";
    $stmt = $conn->prepare($countSQL);
}
$stmt->execute();
$stmt->bind_result($totalTasks);
$stmt->fetch();
$stmt->close();

$totalPages = ceil($totalTasks / $tasksPerPage);

// Fetch tasks
if ($search !== '') {
    $taskSQL = "SELECT tasks.id, tasks.title, tasks.deadline, tasks.priority, tasks.status, tasks.progress, users.fullname AS assigned_name
                FROM tasks
                LEFT JOIN users ON tasks.assigned_to = users.id
                WHERE tasks.title LIKE ? OR users.fullname LIKE ?
                ORDER BY $orderBy
                LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($taskSQL);
    $stmt->bind_param("ssii", $like, $like, $tasksPerPage, $offset);
} else {
    $taskSQL = "SELECT tasks.id, tasks.title, tasks.deadline, tasks.priority, tasks.status, tasks.progress, users.fullname AS assigned_name
                FROM tasks
                LEFT JOIN users ON tasks.assigned_to = users.id
                ORDER BY $orderBy
                LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($taskSQL);
    $stmt->bind_param("ii", $tasksPerPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Monitor Progress - ETMS</title>
<link rel="stylesheet" href="dashboard.css">
<style>
/* Colored status badges */
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 0.85rem;
    color: #fff;
    text-transform: capitalize;
}
.status-not-started { background-color: #007bff; }
.status-in-progress { background-color: #ffc107; color:#000; }
.status-completed { background-color: #28a745; color:#000; }
.status-overdue { background-color: #dc3545; }

/* Progress bar */
.progress-bar {
    background: #e9ecef;
    height: 12px;
    border-radius: 6px;
    overflow: hidden;
}
.progress-fill {
    height: 12px;
    border-radius: 6px;
    width: 0%;
    transition: width 0.3s ease, background-color 0.3s ease;
}

/* Overdue row highlight */
.overdue { background-color: #f8d7da; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-logo">ETMS</div>
  <ul class="sidebar-menu">
    <li><a href="admin-dashboard.php">Dashboard</a></li>
    <li><a href="manage-users.php">Manage Users</a></li>
    <li><a href="create-task.php">Create Task</a></li>
    <li><a href="manage-tasks.php">Manage Tasks</a></li>
    <li><a href="assign-task.php">Assign Tasks</a></li>
    <li><a href="monitor-progress.php" class="active">Monitor Progress</a></li>
    <li><a href="evaluate-employees.php">Evaluate Employees</a></li>
    <li><a href="reports.php">Reports</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</div>

<div class="main-content">
<header><h2>Monitor Task Progress</h2></header>

<section class="controls">
  <form method="GET" class="search-form">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by task or employee" />
    <button type="submit" class="btn">Search</button>
    <?php if($search!==''): ?>
      <a href="monitor-progress.php" class="btn clear-btn">Clear</a>
    <?php endif; ?>
  </form>

  <label for="sort-tasks">Sort by:</label>
  <select id="sort-tasks" onchange="applySort()">
    <option value="deadline" <?= $sort==='deadline'?'selected':'' ?>>Deadline</option>
    <option value="priority" <?= $sort==='priority'?'selected':'' ?>>Priority</option>
    <option value="progress" <?= $sort==='progress'?'selected':'' ?>>Progress</option>
  </select>

  <label for="filter-status">Filter by Status:</label>
  <select id="filter-status" onchange="filterTable()">
    <option value="">All</option>
    <option value="Not Started">Not Started</option>
    <option value="In Progress">In Progress</option>
    <option value="Completed">Completed</option>
  </select>
</section>

<section class="table-section">
<div class="table-container">
<table id="task-table">
<thead>
<tr>
  <th scope="col">Task</th>
  <th scope="col">Assigned To</th>
  <th scope="col">Deadline</th>
  <th scope="col">Priority</th>
  <th scope="col">Status</th>
  <th scope="col">Progress</th>
</tr>
</thead>
<tbody>
<?php if($result->num_rows>0): ?>
  <?php while($row=$result->fetch_assoc()):
      $progress = strtolower($row['status'])==='not started' ? 0 : (int)$row['progress'];
      $color = $progress < 30 ? '#dc3545' : ($progress < 70 ? '#ffc107' : '#28a745');

      $isOverdue = ($row['status']!=='Completed' && $row['deadline'] < $today);
      $rowClass = $isOverdue ? 'overdue' : '';

      $statusClass = 'status-'.strtolower(str_replace(' ','-',$row['status']));
      if($isOverdue) $statusClass = 'status-overdue';
  ?>
  <tr class="<?= $rowClass ?>" data-status="<?= htmlspecialchars($row['status']) ?>">
    <td><?= htmlspecialchars($row['title']) ?></td>
    <td><?= htmlspecialchars($row['assigned_name'] ?? 'Unassigned') ?></td>
    <td><?= htmlspecialchars($row['deadline']) ?></td>
    <td><?= htmlspecialchars($row['priority']) ?></td>
    <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></span></td>
    <td>
      <div class="progress-bar">
        <div class="progress-fill" style="width: <?= $progress ?>%; background-color: <?= $color ?>;"></div>
      </div>
      <?= $progress ?>%
    </td>
  </tr>
  <?php endwhile; ?>
<?php else: ?>
  <tr><td colspan="6" class="no-data">No tasks found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<div class="pagination">
<?php if($totalPages>1):
    $params = $_GET;
    for($i=1;$i<=$totalPages;$i++):
        $params['page'] = $i;
        $pageUrl = 'monitor-progress.php?' . http_build_query($params);
?>
<a href="<?= $pageUrl ?>" class="<?= $i==$page?'current':'' ?>"><?= $i ?></a>
<?php endfor; endif; ?>
</div>
</section>
</div>

<script>
function applySort() {
  const sort = document.getElementById('sort-tasks').value;
  const url = new URL(window.location.href);
  url.searchParams.set('sort', sort);
  window.location.href = url.toString();
}

function filterTable() {
  const filter = document.getElementById('filter-status').value.toLowerCase();
  document.querySelectorAll('#task-table tbody tr').forEach(row => {
    const status = row.getAttribute('data-status').toLowerCase();
    row.style.display = !filter || status===filter ? '' : 'none';
  });
}
</script>

</body>
</html>
