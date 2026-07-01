<?php
session_start();
include 'db.php';

// Only admins and super_admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("Location: admin-login.php");
    exit();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Evaluate Employees - ETMS</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.dropdown { position: absolute; background: #fff; border: 1px solid #ccc; width: 100%; max-height: 200px; overflow-y: auto; display: none; z-index: 1000; }
.dropdown-item { padding: 8px; cursor: pointer; }
.dropdown-item:hover, .dropdown-item.highlight { background: #f0f0f0; }
.loader { display: none; font-size: 0.9rem; color: #555; margin-top: 5px; }
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
    <li><a href="monitor-progress.php">Monitor Progress</a></li>
    <li><a href="evaluate-employees.php" class="active">Evaluate Employees</a></li>
    <li><a href="reports.php">Reports</a></li>
    <li><a href="logout.php" class="logout">Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <header><h2>Evaluate Employees</h2></header>

  <?php if(isset($_SESSION['success'])): ?>
  <p class="success-msg"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
<?php endif; ?>
<?php if(isset($_SESSION['error'])): ?>
  <p class="error-msg"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
<?php endif; ?>


  <form method="POST" action="process-evaluation.php" autocomplete="off" class="form">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <label for="employee_search">Search Employee</label>
      <input type="text" id="employee_search" class="form-control" placeholder="Type to search..." required>
      <div id="employee_dropdown" class="dropdown"></div>
      <input type="hidden" name="employee_id" id="employee_id">

      <label for="task_id">Select Task</label>
      <select name="task_id" id="task_id" class="form-control" disabled required>
        <option value="" disabled selected>Select employee first</option>
      </select>
      <div id="task_loader" class="loader">Loading tasks...</div>

      <label for="suggested_rating">Suggested Rating (System)</label>
      <input type="text" id="suggested_rating" class="form-control" readonly placeholder="Select task to calculate">

      <label for="rating">Final Rating (1-10)</label>
      <input type="number" name="rating" id="rating" class="form-control" min="0" max="10" required>

      <label for="comments">Comments (Optional)</label>
      <textarea name="comments" id="comments" class="form-control" rows="3" placeholder="Enter comments"></textarea>

      <button type="submit" class="btn btn-primary">Submit Evaluation</button>
  </form>
</div>

<script>
const searchInput = document.getElementById('employee_search');
const dropdown = document.getElementById('employee_dropdown');
const hiddenInput = document.getElementById('employee_id');
const taskSelect = document.getElementById('task_id');
const taskLoader = document.getElementById('task_loader');
const suggestedRatingInput = document.getElementById('suggested_rating');

let debounceTimer;
let selectedIndex = -1;

// Render employee dropdown
function renderDropdown(employees) {
    dropdown.innerHTML = '';
    if (!employees.length) {
        const div = document.createElement('div');
        div.textContent = 'No employees found';
        div.classList.add('no-match');
        dropdown.appendChild(div);
        dropdown.style.display = 'block';
        return;
    }
    employees.forEach((e, i) => {
        const div = document.createElement('div');
        div.textContent = e.fullname;
        div.dataset.id = e.id;
        div.dataset.index = i;
        div.classList.add('dropdown-item');
        div.addEventListener('mousedown', () => selectEmployee(e));
        dropdown.appendChild(div);
    });
    dropdown.style.display = 'block';
}

// Select employee and fetch tasks
function selectEmployee(emp) {
    searchInput.value = emp.fullname;
    hiddenInput.value = emp.id;
    dropdown.style.display = 'none';

    taskSelect.disabled = true;
    suggestedRatingInput.value = '';
    taskSelect.innerHTML = '<option value="" disabled selected>Loading tasks...</option>';
    taskLoader.style.display = 'block';

    fetch('fetch-tasks.php?employee_id=' + emp.id)
        .then(res => res.json())
        .then(data => {
            taskLoader.style.display = 'none';
            let options = '<option value="" disabled selected>Choose task</option>';
            if (data.success && data.data.length) {
                data.data.sort((a,b) => new Date(a.deadline) - new Date(b.deadline));
                data.data.forEach(task => {
                    options += `
                        <option 
                            value="${task.id}"
                            data-status="${task.status}"
                            data-progress="${task.progress}"
                            data-priority="${task.priority}"
                            data-deadline="${task.deadline}"
                        >
                            ${task.title} | ${task.status}
                        </option>`;
                });
                taskSelect.disabled = false;
            } else {
                options += `<option value="" disabled>No tasks assigned</option>`;
                taskSelect.disabled = true;
            }
            taskSelect.innerHTML = options;
        })
        .catch(() => {
            taskLoader.style.display = 'none';
            taskSelect.innerHTML = '<option value="" disabled>Error loading tasks</option>';
            taskSelect.disabled = true;
        });
}

// Correct Suggested Rating calculation
taskSelect.addEventListener('change', () => {
    const opt = taskSelect.options[taskSelect.selectedIndex];
    if (!opt) return;

    const progress = parseInt(opt.dataset.progress) || 0;
    const priority = opt.dataset.priority || 'Medium';

    let statusPoints = 0;
    let progressPoints = 0;
    let priorityPoints = 0;

    // Status points
    if (progress >= 100) statusPoints = 10;
    else if (progress > 0) statusPoints = 6;
    else statusPoints = 0;

    // Progress points
    if (progress >= 90) progressPoints = 10;
    else if (progress >= 70) progressPoints = 8;
    else if (progress >= 40) progressPoints = 6;
    else if (progress > 0) progressPoints = 4;
    else progressPoints = 0;

    // Priority points ONLY if task has started
    if (progress > 0) {
        switch(priority) {
            case 'High': priorityPoints = 10; break;
            case 'Medium': priorityPoints = 8; break;
            case 'Low': priorityPoints = 6; break;
            default: priorityPoints = 8;
        }
    } else {
        priorityPoints = 0; // Task not started, priority ignored
    }

    // Weighted: Status 30%, Progress 50%, Priority 20%
    const finalScore = Math.round(statusPoints*0.3 + progressPoints*0.5 + priorityPoints*0.2);

    suggestedRatingInput.value = finalScore + " / 10"; // 0 if task not started
});

// Employee search debounce
searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const val = searchInput.value.trim();
        selectedIndex = -1;
        hiddenInput.value = '';
        taskSelect.disabled = true;
        suggestedRatingInput.value = '';
        taskSelect.innerHTML = '<option value="" disabled selected>Select employee first</option>';
        if (!val) { dropdown.style.display = 'none'; return; }

        fetch('search-employees.php?q=' + encodeURIComponent(val))
            .then(res => res.json())
            .then(data => renderDropdown(data.data || []))
            .catch(() => renderDropdown([]));
    }, 250);
});

// Keyboard navigation
searchInput.addEventListener('keydown', e => {
    const items = dropdown.querySelectorAll('.dropdown-item');
    if (!items.length) return;
    if (e.key === 'ArrowDown') { selectedIndex = (selectedIndex + 1) % items.length; highlight(items); e.preventDefault(); }
    if (e.key === 'ArrowUp') { selectedIndex = (selectedIndex - 1 + items.length) % items.length; highlight(items); e.preventDefault(); }
    if (e.key === 'Enter') {
        if (selectedIndex >= 0)
            selectEmployee({id: items[selectedIndex].dataset.id, fullname: items[selectedIndex].textContent});
        e.preventDefault();
    }
});

function highlight(items) {
    items.forEach((item, i) => item.classList.toggle('highlight', i === selectedIndex));
}

searchInput.addEventListener('blur', () => setTimeout(() => dropdown.style.display = 'none', 200));
</script>

</body>
</html>