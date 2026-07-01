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
    $query = "SELECT id, title, progress, status, updated_at FROM tasks WHERE assigned_to = ? AND title LIKE ? ORDER BY updated_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $like = "%$search%";
    $stmt->bind_param("isii", $employee_id, $like, $tasksPerPage, $offset);
} else {
    $query = "SELECT id, title, progress, status, updated_at FROM tasks WHERE assigned_to = ? ORDER BY updated_at DESC LIMIT ? OFFSET ?";
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
    <title>Update Progress - ETMS</title>
    <link rel="stylesheet" href="dashboard.css" />
    <style>
        /* Subtle styling for Last Updated */
        .last-updated {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: -0.3rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">ETMS</div>
    <ul class="sidebar-menu">
        <li><a href="employee-dashboard.php">Dashboard</a></li>
        <li><a href="my-tasks.php">My Tasks</a></li>
        <li><a href="update-progress.php" class="active">Update Progress</a></li>
        <li><a href="employee-evaluations.php">My Evaluations</a></li>
        <li><a href="logout.php" class="logout">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <header>
        <h2>Update Task Progress</h2>
    </header>

    <form method="GET" style="margin-bottom: 1.5rem;">
        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search by task title..." style="padding: 0.5rem 1rem; border-radius: 5px; border: 1px solid #ccc; width: 250px;" />
        <button type="submit" class="btn" style="margin-left: 8px;">Search</button>
        <?php if ($search !== ''): ?>
            <a href="update-progress.php" class="btn" style="margin-left: 8px; background-color: #6c757d;">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (isset($_SESSION['success'])): ?>
        <p class="success-msg"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <p class="error-msg"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="progress-container" style="margin-bottom: 2rem;">
                <h3><?= htmlspecialchars($row['title']); ?></h3>

                <!-- Last Updated -->
                <p class="last-updated">
                    Last Updated: <?= date('d M Y, H:i', strtotime($row['updated_at'])); ?>
                </p>

                <form action="update-progress-process.php" method="POST">
                    <input type="hidden" name="task_id" value="<?= (int)$row['id']; ?>" />

                    <label for="progress-<?= $row['id']; ?>">
                        Progress: <span id="progress-value-<?= $row['id']; ?>"><?= (int)$row['progress']; ?>%</span>
                    </label>
                    <input type="range" id="progress-<?= $row['id']; ?>" name="progress"
                           min="0" max="100" value="<?= (int)$row['progress']; ?>" class="slider"
                           oninput="updateProgress(this.value, <?= $row['id']; ?>)" />

                    <div class="progress-bar">
                        <div id="progress-fill-<?= $row['id']; ?>" class="progress-fill"
                             style="width: <?= (int)$row['progress']; ?>%; background-color: <?= 
                             $row['progress'] < 30 ? '#dc3545' : ($row['progress'] < 70 ? '#ffc107' : '#28a745'); ?>;">
                        </div>
                    </div>

                    <label for="status-<?= $row['id']; ?>">Status:</label>
                    <select name="status" id="status-<?= $row['id']; ?>" onchange="statusManuallyChanged(<?= $row['id']; ?>)">
                        <option value="Not Started" <?= $row['status'] === 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                        <option value="In Progress" <?= $row['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>

                    <button type="submit" class="btn" style="margin-top: 10px;">Update</button>
                </form>
            </div>
        <?php endwhile; ?>

        <!-- Pagination -->
        <div style="margin-top: 2rem; text-align: center;">
            <?php
            $totalPages = ceil($totalTasks / $tasksPerPage);
            if ($totalPages > 1):
                $baseUrl = 'update-progress.php?';
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
        <p>No tasks assigned to you.</p>
    <?php endif; ?>
</div>

<script>
    const manualStatusChanged = {};

    function updateProgress(value, id) {
        const progressEl = document.getElementById('progress-value-' + id);
        const fill = document.getElementById('progress-fill-' + id);
        const statusSelect = document.getElementById('status-' + id);

        progressEl.textContent = value + '%';
        fill.style.width = value + '%';

        let color;
        if (value < 1) color = '#dc3545';
        else if (value < 100) color = '#ffc107';
        else color = '#28a745';
        fill.style.backgroundColor = color;

        if (!manualStatusChanged[id]) {
            if (value < 1) statusSelect.value = 'Not Started';
            else if (value < 100) statusSelect.value = 'In Progress';
            else statusSelect.value = 'Completed';
        }
    }

    function statusManuallyChanged(id) {
        manualStatusChanged[id] = true;

        const statusSelect = document.getElementById('status-' + id);
        const slider = document.getElementById('progress-' + id);
        const fill = document.getElementById('progress-fill-' + id);

        let value = parseInt(slider.value);

        if (statusSelect.value === 'Not Started') value = 0;
        else if (statusSelect.value === 'Completed') value = 100;

        slider.value = value;
        document.getElementById('progress-value-' + id).textContent = value + '%';
        fill.style.width = value + '%';
        fill.style.backgroundColor = value < 30 ? '#dc3545' : (value < 70 ? '#ffc107' : '#28a745');
    }
</script>

</body>
</html>
