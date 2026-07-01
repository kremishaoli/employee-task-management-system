<?php
session_start();
include 'db.php';

// Only admin and super_admin can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("Location: login.php");
    exit();
}

$selfId = (int)$_SESSION['user_id'];
$selfRole = $_SESSION['role'];

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
<title>Manage Users - ETMS</title>
<link rel="stylesheet" href="dashboard.css">
<style>
#loading { display: none; text-align: center; margin: 1rem 0; font-weight: bold; color: #0056b3; }
.btn[disabled] { opacity: 0.6; cursor: not-allowed; }
.pagination-btn.current { background-color: #0056b3; color: white; }
#add-user-form { display:none; margin-bottom: 1.5rem; border:1px solid #ccc; padding:1rem; border-radius:8px; background:#f9f9f9; }
#add-user-form input[type=text], #add-user-form input[type=email], #add-user-form input[type=password] { width: 100%; padding: 6px; margin-bottom: 8px; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-logo">ETMS</div>
  <ul class="sidebar-menu">
    <li><a href="admin-dashboard.php">Dashboard</a></li>
    <li><a href="manage-users.php" class="active">Manage Users</a></li>
    <li><a href="create-task.php">Create Task</a></li>
    <li><a href="manage-tasks.php">Manage Tasks</a></li>
    <li><a href="assign-task.php">Assign Tasks</a></li>
    <li><a href="monitor-progress.php">Monitor Progress</a></li>
    <li><a href="evaluate-employees.php">Evaluate Employees</a></li>
    <li><a href="reports.php">Reports</a></li>
    <li><a href="logout.php" class="logout">Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <header><h2>Manage Users</h2></header>

  <?php if (!empty($_SESSION['error'])): ?>
    <p class="error-msg"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
  <?php endif; ?>
  <?php if (!empty($_SESSION['success'])): ?>
    <p class="success-msg"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
  <?php endif; ?>

  <?php if($selfRole === 'super_admin'): ?>
  <button id="add-user-btn" class="btn">Add New User</button>

  <div id="add-user-form">
    <form id="addUserForm" action="add-user.php" method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <label>Full Name:</label>
      <input type="text" name="fullname" required minlength="3">

      <label>Username:</label>
      <input type="text" name="username" required minlength="3">

      <label>Email:</label>
      <input type="email" name="email" required>

      <label>Password:</label>
      <input type="password" id="password" name="password" required minlength="6">

      <label>Confirm Password:</label>
      <input type="password" id="confirmPassword" name="confirm_password" required minlength="6">

      <label>
        <input type="checkbox" id="showPasswordToggle">
        Show Password
      </label>

      <label>Role:</label>
      <input type="radio" name="role" value="employee" checked> Employee
      <input type="radio" name="role" value="admin"> Admin
      <br><br>

      <button type="submit" class="btn">Create User</button>
    </form>
  </div>
  <?php endif; ?>

  <form id="search-form" style="margin:1rem 0;">
    <input type="text" id="search-input" name="search" placeholder="Search by name, username, or email..." autocomplete="off">
    <button type="submit" class="btn">Search</button>
    <button type="button" id="clear-search" class="btn" style="background-color: #6c757d; margin-left: 8px; display:none;">Clear</button>
  </form>

  <div id="loading">Loading users...</div>

  <table class="styled-table" id="users-table">
    <thead>
      <tr>
        <th>Full Name</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
  <div id="pagination" style="margin-top: 2rem; text-align: center;"></div>
</div>

<script>
const selfId = <?= $selfId ?>;
const selfRole = '<?= $selfRole ?>';

function escapeHtml(text) {
  return text.replace(/[&<>"']/g, m => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[m]));
}

async function fetchUsers(search = '', page = 1) {
  const tbody = document.querySelector('#users-table tbody');
  const paginationDiv = document.getElementById('pagination');
  const loading = document.getElementById('loading');
  tbody.innerHTML = '';
  paginationDiv.innerHTML = '';
  loading.style.display = 'block';

  try {
    const params = new URLSearchParams({ search, page });
    const res = await fetch('fetch-users.php?' + params.toString());
    if (!res.ok) throw new Error('Network error');
    const data = await res.json();
    loading.style.display = 'none';

    if (data.error) {
      tbody.innerHTML = `<tr><td colspan="5">${escapeHtml(data.error)}</td></tr>`;
      return;
    }
    if (data.users.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5">No users found.</td></tr>';
      return;
    }

    data.users.forEach(user => {
      if (selfRole === 'admin' && user.role !== 'employee') return;
      const isSelf = (user.id == selfId);
      let actionButtons = '';

      if (selfRole === 'super_admin' && !isSelf) {
        if (user.role === 'employee') {
          actionButtons += `<form method="POST" action="manage-users-action.php" style="display:inline;">
            <input type="hidden" name="promote_id" value="${user.id}">
            <button type="submit" class="btn">Promote to Admin</button>
          </form>`;
        }
        if (user.role === 'admin') {
          actionButtons += `<form method="POST" action="manage-users-action.php" style="display:inline;">
            <input type="hidden" name="demote_id" value="${user.id}">
            <button type="submit" class="btn delete-btn">Demote to Employee</button>
          </form>`;
        }
      }

      if (!isSelf && (selfRole === 'super_admin' || (selfRole === 'admin' && user.role === 'employee'))) {
        actionButtons += `<form method="POST" action="manage-users-action.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
          <input type="hidden" name="delete_id" value="${user.id}">
          <button type="submit" class="btn delete-btn" style="margin-left: 5px;">Delete</button>
        </form>`;
      }

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${escapeHtml(user.fullname)}</td>
        <td>${escapeHtml(user.username)}</td>
        <td>${escapeHtml(user.email)}</td>
        <td>${escapeHtml(user.role)}</td>
        <td>${actionButtons}</td>`;
      tbody.appendChild(tr);
    });

    if (data.totalPages > 1) {
      for (let i = 1; i <= data.totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = 'btn pagination-btn' + (i === data.currentPage ? ' current' : '');
        btn.addEventListener('click', () => fetchUsers(search, i));
        paginationDiv.appendChild(btn);
      }
    }
  } catch(e) {
    loading.style.display = 'none';
    tbody.innerHTML = `<tr><td colspan="5">Failed to load users.</td></tr>`;
    console.error(e);
  }
}

// Search
const searchInput = document.getElementById('search-input');
let searchTimeout;
searchInput.addEventListener('input', () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    const val = searchInput.value.trim();
    fetchUsers(val, 1);
    document.getElementById('clear-search').style.display = val ? 'inline-block' : 'none';
  }, 300);
});
document.getElementById('search-form').addEventListener('submit', e => {
  e.preventDefault();
  fetchUsers(searchInput.value.trim(), 1);
});
document.getElementById('clear-search').addEventListener('click', () => {
  searchInput.value = '';
  fetchUsers('', 1);
  document.getElementById('clear-search').style.display = 'none';
});

// Toggle Add User form
const addBtn = document.getElementById('add-user-btn');
if(addBtn) {
  addBtn.addEventListener('click', () => {
    const form = document.getElementById('add-user-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
  });
}

// Show / Hide Password + Confirm check
const toggle = document.getElementById('showPasswordToggle');
if (toggle) {
  const pwd = document.getElementById('password');
  const confirmPwd = document.getElementById('confirmPassword');

  toggle.addEventListener('change', () => {
    const type = toggle.checked ? 'text' : 'password';
    pwd.type = type;
    confirmPwd.type = type;
  });

  document.getElementById('addUserForm').addEventListener('submit', (e) => {
    if (pwd.value !== confirmPwd.value) {
      e.preventDefault();
      alert("Passwords do not match!");
    }
  });
}

// Initial fetch
fetchUsers();
</script>
</body>
</html>