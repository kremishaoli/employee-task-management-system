<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("Location: login.php");
    exit();
}
$selfRole = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Manage Tasks - ETMS</title>
<link rel="stylesheet" href="dashboard.css" />
<style>
#search-input { padding: 8px; width: 300px; }
.task-table th, .task-table td { text-align: left; padding: 8px; }
.no-data-msg { text-align: center; font-style: italic; }
.pagination-btn.current { background-color: #0056b3; color: #fff; }
.btn.assign-btn { background-color: #28a745; color: #fff; margin-left:5px; }

/* ===== Page-specific: Description column wraps with tooltip ===== */
.manage-tasks-section .desc-cell {
    display: block;             /* allows multiple lines */
    max-width: 300px;           /* adjust width as needed */
    white-space: normal;        /* allow wrapping */
    overflow-wrap: break-word;  /* break long words */
    cursor: pointer;            /* indicate hover tooltip */
}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo">ETMS</div>
  <ul class="sidebar-menu">
    <li><a href="admin-dashboard.php">Dashboard</a></li>
    <li><a href="manage-users.php">Manage Users</a></li>
    <li><a href="create-task.php">Create Task</a></li>
    <li><a href="manage-tasks.php" class="active">Manage Tasks</a></li>
    <li><a href="assign-task.php">Assign Tasks</a></li>
    <li><a href="monitor-progress.php">Monitor Progress</a></li>
    <li><a href="evaluate-employees.php">Evaluate Employees</a></li>
    <li><a href="reports.php">Reports</a></li>
    <li><a href="logout.php" class="logout">Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <header><h2>Manage Tasks</h2></header>

  <div style="margin-bottom:1.5rem;">
    <input type="text" id="search-input" placeholder="Search by title, description, or employee..." autocomplete="off">
    <button type="button" id="clear-search" class="btn" style="display:none; margin-left:8px; background:#6c757d;">Clear</button>
  </div>

  <section class="table-section manage-tasks-section">
    <div style="overflow-x:auto;">
      <table class="task-table" id="tasks-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Title</th>
            <th>Description</th>
            <th>Deadline</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Progress</th>
            <th>Assigned To</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <div id="pagination" style="margin-top:1rem; text-align:center;"></div>
  </section>
</div>

<script>
const selfRole = '<?= $selfRole ?>';
const tasksPerPage = 5;

function escapeHtml(text) {
  return text?.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])) || '';
}

async function fetchTasks(search='', page=1) {
  try {
    const res = await fetch(`fetch-tasks.php?search=${encodeURIComponent(search)}&page=${page}`);
    const data = await res.json();
    const tbody = document.querySelector('#tasks-table tbody');
    tbody.innerHTML = '';

    if(!data.success || data.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9" class="no-data-msg">No tasks found.</td></tr>';
    } else {
      data.data.forEach((task, index) => {
        let actions = '';
        if(task.status !== 'Completed') {
          actions += `<a href="assign-task.php?task_id=${task.id}&current_emp=${task.assigned_employee_id || 0}" class="btn assign-btn">Assign / Reassign</a>`;
        }

        if(selfRole === 'super_admin' || selfRole === 'admin') {
          actions = `<a href="edit-task.php?id=${task.id}" class="btn edit-btn">Edit</a> ` +
                    `<a href="delete-task.php?id=${task.id}" class="btn delete-btn" onclick="return confirm('Delete this task?');">Delete</a> ` +
                    actions;
        }

        const displayIndex = (page - 1) * tasksPerPage + index + 1;
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${displayIndex}</td>
          <td>${escapeHtml(task.title)}</td>
          <!-- Description wraps into multiple lines with tooltip -->
          <td>
            <span class="desc-cell" title="${escapeHtml(task.description)}">
              ${escapeHtml(task.description)}
            </span>
          </td>
          <td>${escapeHtml(task.deadline)}</td>
          <td>${escapeHtml(task.priority)}</td>
          <td>${escapeHtml(task.status)}</td>
          <td>${task.progress}%</td>
          <td>${task.assigned_employee ? escapeHtml(task.assigned_employee) : 'Not Assigned'}</td>
          <td>${actions}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    const paginationDiv = document.getElementById('pagination');
    paginationDiv.innerHTML = '';
    for(let i=1; i<=data.totalPages; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      btn.className = 'btn pagination-btn' + (i===data.currentPage ? ' current':'' );
      btn.addEventListener('click', () => fetchTasks(search, i));
      paginationDiv.appendChild(btn);
    }

  } catch(e) {
    console.error(e);
    alert('Failed to load tasks.');
  }
}

document.getElementById('search-input').addEventListener('keyup', e => {
  const val = e.target.value.trim();
  fetchTasks(val, 1);
  document.getElementById('clear-search').style.display = val ? 'inline-block' : 'none';
});

document.getElementById('clear-search').addEventListener('click', () => {
  document.getElementById('search-input').value = '';
  fetchTasks('', 1);
  document.getElementById('clear-search').style.display = 'none';
});

fetchTasks();
</script>
</body>
</html>
