<?php
session_start();
include 'db.php';

// Only allow admin and super_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("Location: login.php");
    exit();
}

// Preselected task from GET
$preselectedTaskId = intval($_GET['task_id'] ?? 0);

// Fetch all tasks except completed
$tasksQuery = "
    SELECT t.id, t.title, t.status, t.assigned_to, u.fullname AS assigned_employee
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.status IS NULL OR t.status != 'Completed'
    ORDER BY t.created_at DESC
";
$tasksResult = $conn->query($tasksQuery);

// Fetch all employees
$employeesResult = $conn->query("SELECT id, fullname FROM users WHERE role='employee' ORDER BY fullname ASC");
$employees = [];
while($emp = $employeesResult->fetch_assoc()){
    $employees[$emp['id']] = $emp['fullname'];
}

// Build task map for preselection
$taskAssignments = [];
while($task = $tasksResult->fetch_assoc()) {
    $taskAssignments[$task['id']] = $task;
}
// Reset result pointer
$tasksResult->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign / Reassign Task - ETMS</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.task-form select option[disabled] { color: #999; }
.success-msg { color: green; }
.error-msg { color: red; }
.no-data-msg { font-style: italic; text-align: center; margin-top: 1rem; }
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
        <li><a href="assign-task.php" class="active">Assign Tasks</a></li>
        <li><a href="monitor-progress.php">Monitor Progress</a></li>
        <li><a href="evaluate-employees.php">Evaluate Employees</a></li>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="logout.php" class="logout">Logout</a></li>
    </ul>
</div>

<div class="main-content">
<header><h2>Assign / Reassign Task</h2></header>

<section class="form-section">
<?php if(isset($_SESSION['success'])): ?>
    <p class="success-msg"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
<?php endif; ?>
<?php if(isset($_SESSION['error'])): ?>
    <p class="error-msg"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
<?php endif; ?>

<?php if($tasksResult->num_rows === 0): ?>
    <p class="no-data-msg">No tasks available for assignment.</p>
<?php else: ?>
<form id="assignForm" action="assign-task-process.php" method="POST" class="task-form">
    <!-- Task Dropdown -->
    <label for="task">Select Task</label>
    <select id="task" name="task_id" required>
        <option value="" disabled <?= $preselectedTaskId ? '' : 'selected' ?>>Choose a task</option>
        <?php while($task = $tasksResult->fetch_assoc()): ?>
            <option value="<?= $task['id']; ?>" <?= ($task['id'] == $preselectedTaskId) ? 'selected' : '' ?>>
                <?= htmlspecialchars($task['title']); ?> 
                (Status: <?= $task['status'] ?? 'Not Started' ?><?= $task['assigned_employee'] ? ", Assigned to: " . htmlspecialchars($task['assigned_employee']) : ", Unassigned" ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <!-- Employee Dropdown -->
    <label for="employee">Select Employee</label>
    <select id="employee" name="employee_id" required>
        <option value="0">-- Unassigned --</option>
        <?php foreach($employees as $id => $name): ?>
            <option value="<?= $id ?>"
                <?php 
                if($preselectedTaskId && isset($taskAssignments[$preselectedTaskId])) {
                    echo ($taskAssignments[$preselectedTaskId]['assigned_to'] == $id) ? 'selected' : '';
                }
                ?>
            >
                <?= htmlspecialchars($name) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn">Assign / Reassign</button>
</form>
<?php endif; ?>
</section>
</div>

<script>
document.getElementById('assignForm')?.addEventListener('submit', function(e){
    if(!document.getElementById('task').value || !document.getElementById('employee').value){
        alert('Please select both task and employee.');
        e.preventDefault();
    }
});
</script>
</body>
</html>
