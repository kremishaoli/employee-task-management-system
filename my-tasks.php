<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location:login.php");
    exit();
}

include 'db.php';

$employee_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$tasksPerPage = 5;
$offset = ($page - 1) * $tasksPerPage;

// Count total tasks for pagination
if ($search !== '') {
    $countQuery = "SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND title LIKE ?";
    $countStmt = $conn->prepare($countQuery);
    $like = "%$search%";
    $countStmt->bind_param("is", $employee_id, $like);
} else {
    $countQuery = "SELECT COUNT(*) FROM tasks WHERE assigned_to = ?";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param("i", $employee_id);
}
$countStmt->execute();
$countStmt->bind_result($totalTasks);
$countStmt->fetch();
$countStmt->close();

// Fetch paginated tasks
if ($search !== '') {
    $query = "SELECT * FROM tasks WHERE assigned_to = ? AND title LIKE ? LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $like = "%$search%";
    $stmt->bind_param("isii", $employee_id, $like, $tasksPerPage, $offset);
} else {
    $query = "SELECT * FROM tasks WHERE assigned_to = ? LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $employee_id, $tasksPerPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Tasks - ETMS</title>
    <link rel="stylesheet" href="dashboard.css" />
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo">ETMS</div>
    <ul class="sidebar-menu">
        <li><a href="employee-dashboard.php">Dashboard</a></li>
        <li><a href="my-tasks.php" class="active">My Tasks</a></li>
        <li><a href="update-progress.php">Update Progress</a></li>
        <li><a href="employee-evaluations.php">My Evaluations</a></li>
        <li><a href="logout.php" class="logout">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <header><h2>My Tasks</h2></header>
    <form method="GET" style="margin-bottom: 1.5rem;">
        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search by task title..." style="padding: 0.5rem 1rem; border-radius: 5px; border: 1px solid #ccc; width: 250px;" />
        <button type="submit" class="btn" style="margin-left: 8px;">Search</button>
        <?php if ($search !== ''): ?>
            <a href="my-tasks.php" class="btn" style="margin-left: 8px; background-color: #6c757d;">Clear</a>
        <?php endif; ?>
    </form>
    <section class="table-container">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Deadline</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($task = $result->fetch_assoc()): 
                    $isOverdue = ($task['deadline'] < $today) && ($task['status'] !== 'Completed');
                    $statusClass = strtolower(str_replace(' ', '-', $task['status']));
                ?>
                    <tr <?= $isOverdue ? 'class="overdue-row"' : ''; ?>>
                        <td><?= htmlspecialchars($task['title']); ?></td>
                        <td><?= htmlspecialchars($task['description']); ?></td>
                        <td>
                            <?= htmlspecialchars($task['deadline']); ?>
                            <?php if ($isOverdue): ?>
                                <span class="status-badge overdue">Overdue</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($task['priority']); ?></td>
                        <td>
                            <span class="status-badge <?= $statusClass; ?>">
                                <?= htmlspecialchars($task['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="
                                    width: <?= (int)$task['progress']; ?>%;
                                    background-color: <?= $task['progress'] < 30 ? '#dc3545' : ($task['progress'] < 70 ? '#ffc107' : '#28a745'); ?>;">
                                </div>
                            </div>
                            <span><?= (int)$task['progress']; ?>%</span>
                        </td>
                        <td>
                            <a href="update-progress.php?id=<?= (int)$task['id']; ?>" class="btn action-btn">Update</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="no-data-msg">No tasks assigned yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <!-- Pagination links -->
        <div style="margin-top: 2rem; text-align: center;">
            <?php
            $totalPages = ceil($totalTasks / $tasksPerPage);
            if ($totalPages > 1):
                $baseUrl = 'my-tasks.php?';
                if ($search !== '') {
                    $baseUrl .= 'search=' . urlencode($search) . '&';
                }
                for ($i = 1; $i <= $totalPages; $i++):
            ?>
                <a href="<?= $baseUrl . 'page=' . $i; ?>" class="btn" style="margin: 0 4px; <?= $i == $page ? 'background-color: #0056b3;' : '' ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; endif; ?>
        </div>
    </section>
</div>
</body>
</html>
