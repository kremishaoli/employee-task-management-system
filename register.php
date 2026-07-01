<?php
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register - ETMS</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

<header>
  <div class="logo">ETMS</div>
  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="info.php">Info</a></li>
      <li><a href="register.php" class="active">Register</a></li>
      <li><a href="login.php">Login</a></li>
    </ul>
  </nav>
</header>

<section class="login-section">
  <h2>Register New User</h2>

  <?php
    if (!empty($_SESSION['error'])) {
        echo '<p class="error-message">' . htmlspecialchars($_SESSION['error']) . '</p>';
        unset($_SESSION['error']);
    }
    if (!empty($_SESSION['success'])) {
        echo '<p class="success-message">' . htmlspecialchars($_SESSION['success']) . '</p>';
        unset($_SESSION['success']);
    }
  ?>

  <form id="registerForm" action="register-process.php" method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <label for="fullname">Full Name</label>
    <input type="text" id="fullname" name="fullname" placeholder="Enter full name" required minlength="3" />

    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="Enter email" required />

    <label for="username">Username</label>
    <input type="text" id="username" name="username" placeholder="Choose username" required minlength="3" />

    <label for="password">Password</label>
    <input type="password" id="password" name="password" placeholder="Choose password" required minlength="8" />

    <label for="confirmPassword">Confirm Password</label>
    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required minlength="8" />

    <label><input type="checkbox" id="showPasswordToggle" /> Show Password</label>

    <button type="submit">Register</button>
  </form>

  <a href="index.php" class="back-home">← Back to Home</a>
</section>

<script>
const toggle = document.getElementById('showPasswordToggle');
const pwd = document.getElementById('password');
const confirmPwd = document.getElementById('confirmPassword');

toggle.addEventListener('change', () => {
  const type = toggle.checked ? 'text' : 'password';
  pwd.type = type;
  confirmPwd.type = type;
});

// Simple inline validation
document.getElementById('registerForm').addEventListener('submit', (e) => {
  const errors = [];
  if (document.getElementById('fullname').value.trim().length < 3) errors.push("Full name too short.");
  if (document.getElementById('username').value.trim().length < 3) errors.push("Username too short.");
  if (pwd.value !== confirmPwd.value) errors.push("Passwords do not match.");

  if (errors.length > 0) {
    e.preventDefault();
    let box = document.querySelector('.error-message');
    if (!box) {
      box = document.createElement('p');
      box.className = 'error-message';
      document.getElementById('registerForm').prepend(box);
    }
    box.textContent = errors.join(' ');
  }
});
</script>

</body>
</html>
