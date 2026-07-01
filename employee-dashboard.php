<?php
session_start();

// ✅ Only allow employees
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php"); // unified login page
    exit();
}

include 'db.php';
$employee_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Fetch all stats in one go to reduce repeated prepare statements
$taskStatsQuery = $conn->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status != 'Completed' AND deadline < ? THEN 1 ELSE 0 END) AS overdue
    FROM tasks 
    WHERE assigned_to = ?
");
$taskStatsQuery->bind_param("si", $today, $employee_id);
$taskStatsQuery->execute();
$stats = $taskStatsQuery->get_result()->fetch_assoc();

$totalTasks     = $stats['total'] ?? 0;
$completedTasks = $stats['completed'] ?? 0;
$overdueTasks   = $stats['overdue'] ?? 0;
$pendingTasks   = $totalTasks - $completedTasks;

// Average rating
$avgRatingQuery = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM evaluations WHERE employee_id = ?");
$avgRatingQuery->bind_param("i", $employee_id);
$avgRatingQuery->execute();
$avgRating = round($avgRatingQuery->get_result()->fetch_assoc()['avg_rating'] ?? 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Employee Dashboard - ETMS</title>
    <link rel="stylesheet" href="dashboard.css" />
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo">ETMS</div>
    <ul class="sidebar-menu">
        <li><a href="employee-dashboard.php" class="active">Dashboard</a></li>
        <li><a href="my-tasks.php">My Tasks</a></li>
        <li><a href="update-progress.php">Update Progress</a></li>
        <li><a href="employee-evaluations.php">My Evaluations</a></li>
        <li><a href="logout.php" class="logout">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <header>
        <h2>Welcome, <?= htmlspecialchars($_SESSION['fullname']); ?>!</h2>
    </header>

    <!-- Overview Cards -->
    <section class="dashboard-overview">
        <h3>Overview</h3>
        <div class="card-grid">
            <?php
            $cards = [
                ['title'=>'Total Tasks', 'value'=>$totalTasks],
                ['title'=>'Completed Tasks', 'value'=>$completedTasks],
                ['title'=>'Pending Tasks', 'value'=>$pendingTasks],
                ['title'=>'Overdue Tasks', 'value'=>$overdueTasks],
                ['title'=>'Average Rating', 'value'=>$avgRating > 0 ? $avgRating.' ⭐' : 'Not Rated Yet']
            ];

            foreach ($cards as $card):
                $cardClass = ($card['title'] === 'Overdue Tasks' && $overdueTasks > 0) ? 'overdue-highlight' : '';
            ?>
            <div class="card <?= $cardClass; ?>">
                <h4><?= $card['title']; ?></h4>
                <p>
                    <?= $card['title'] === 'Overdue Tasks' ? ($overdueTasks > 0 ? $overdueTasks : '0') : $card['value']; ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="dashboard-overview">
        <h3>Quick Actions</h3>
        <div class="card-grid">
            <?php
            $actions = [
                ['title'=>'My Tasks', 'desc'=>'View all tasks assigned to you.', 'link'=>'my-tasks.php'],
                ['title'=>'Update Progress', 'desc'=>'Update the status or progress of your tasks.', 'link'=>'update-progress.php'],
                ['title'=>'My Evaluations', 'desc'=>'Check your ratings and feedback from admins.', 'link'=>'employee-evaluations.php']
            ];

            foreach ($actions as $action): ?>
                <div class="card">
                    <h4><?= $action['title']; ?></h4>
                    <p><?= $action['desc']; ?></p>
                    <a href="<?= $action['link']; ?>" class="btn">Go</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
</body>
</html>
