<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

include 'db.php';
$employee_id = $_SESSION['user_id'];


$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$evalsPerPage = 5;
$offset = ($page - 1) * $evalsPerPage;

// Count total evaluations for pagination
if ($search !== '') {
    $countQuery = "SELECT COUNT(*) FROM evaluations e JOIN tasks t ON e.task_id = t.id WHERE e.employee_id = ? AND (t.title LIKE ? OR e.comments LIKE ?)";
    $countStmt = $conn->prepare($countQuery);
    $like = "%$search%";
    $countStmt->bind_param("iss", $employee_id, $like, $like);
} else {
    $countQuery = "SELECT COUNT(*) FROM evaluations WHERE employee_id = ?";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param("i", $employee_id);
}
$countStmt->execute();
$countStmt->bind_result($totalEvals);
$countStmt->fetch();
$countStmt->close();

// Fetch paginated evaluations
if ($search !== '') {
    $query = "SELECT e.rating, e.comments, e.created_at, t.title AS task_title, a.fullname AS admin_name FROM evaluations e JOIN tasks t ON e.task_id = t.id JOIN users a ON e.evaluated_by = a.id WHERE e.employee_id = ? AND (t.title LIKE ? OR e.comments LIKE ?) ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $like = "%$search%";
    $stmt->bind_param("issii", $employee_id, $like, $like, $evalsPerPage, $offset);
} else {
    $query = "SELECT e.rating, e.comments, e.created_at, t.title AS task_title, a.fullname AS admin_name FROM evaluations e JOIN tasks t ON e.task_id = t.id JOIN users a ON e.evaluated_by = a.id WHERE e.employee_id = ? ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $employee_id, $evalsPerPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= ($i <= $rating) ? '⭐' : '☆';
    }
    return "<span class='stars'>$stars</span>";
}

function getBadge($rating) {
    if ($rating >= 4) return "<span class='badge excellent'>Excellent</span>";
    if ($rating == 3) return "<span class='badge good'>Good</span>";
    if ($rating == 2) return "<span class='badge average'>Average</span>";
    return "<span class='badge poor'>Poor</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Evaluations - ETMS</title>
    <link rel="stylesheet" href="dashboard.css" />
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">ETMS</div>
    <ul class="sidebar-menu">
        <li><a href="employee-dashboard.php">Dashboard</a></li>
        <li><a href="my-tasks.php">My Tasks</a></li>
        <li><a href="update-progress.php">Update Progress</a></li>
        <li><a href="employee-evaluations.php" class="active">My Evaluations</a></li>
        <li><a href="logout.php" class="logout">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <header><h2>My Evaluations</h2></header>
    <form method="GET" style="margin-bottom: 1.5rem;">
        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search by task or comments..." style="padding: 0.5rem 1rem; border-radius: 5px; border: 1px solid #ccc; width: 250px;" />
        <button type="submit" class="btn" style="margin-left: 8px;">Search</button>
        <?php if ($search !== ''): ?>
            <a href="employee-evaluations.php" class="btn" style="margin-left: 8px; background-color: #6c757d;">Clear</a>
        <?php endif; ?>
    </form>
    <section>
        <?php if ($result->num_rows > 0): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Rating</th>
                        <th>Badge</th>
                        <th>Comments</th>
                        <th>Evaluated By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($eval = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($eval['task_title']); ?></td>
                            <td><?= renderStars($eval['rating']); ?></td>
                            <td><?= getBadge($eval['rating']); ?></td>
                            <td><?= nl2br(htmlspecialchars($eval['comments'])); ?></td>
                            <td><?= htmlspecialchars($eval['admin_name']); ?></td>
                            <td><?= htmlspecialchars(date("d M Y", strtotime($eval['created_at']))); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <!-- Pagination links -->
            <div style="margin-top: 2rem; text-align: center;">
                <?php
                $totalPages = ceil($totalEvals / $evalsPerPage);
                if ($totalPages > 1):
                    $baseUrl = 'employee-evaluations.php?';
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
        <?php else: ?>
            <p>No evaluations available yet.</p>
        <?php endif; ?>
    </section>
</div>

</body>
</html>
