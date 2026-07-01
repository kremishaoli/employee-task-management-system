<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Employee Login - ETMS</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

<header>
  <div class="logo">ETMS</div>
  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="info.php">Info</a></li>
      <li><a href="register.php">Register</a></li>
      <li><a href="admin-login.php">Admin Login</a></li>
      <li><a href="employee-login.php" class="active">Employee Login</a></li>
    </ul>
  </nav>
</header>

<section class="login-section">
  <h2>Employee Login</h2>

  <?php
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    if (isset($_SESSION['error'])) {
        echo '<p class="error-message">' . htmlspecialchars($_SESSION['error']) . '</p>';
        unset($_SESSION['error']);
    }
  ?>

  <form action="login-process.php" method="POST" id="loginFormEmployee" novalidate>
    <input type="hidden" name="login_type" value="employee" />
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />

    <label for="usernameOrEmail">Username or Email</label>
    <input type="text" id="usernameOrEmail" name="usernameOrEmail" placeholder="Enter your username or email" required autofocus />

    <label for="password">Password</label>
    <div class="password-wrapper">
      <input type="password" id="password" name="password" placeholder="Enter your password" required />
      <span class="toggle-password" id="togglePwd">Show</span>
    </div>

    <button type="submit">Login</button>
  </form>

  <a href="index.php" class="back-home">← Back to Home</a>
</section>

<script>
  // Toggle password visibility
  document.getElementById('togglePwd').addEventListener('click', function () {
    const pwd = document.getElementById('password');
    const isText = pwd.type === 'text';
    pwd.type = isText ? 'password' : 'text';
    this.textContent = isText ? 'Show' : 'Hide';
  });

  // Inline validation message (no alerts)
  const form = document.getElementById('loginFormEmployee');
  const errorBox = document.createElement('p');
  errorBox.className = 'error-message';
  errorBox.style.display = 'none';
  form.prepend(errorBox);

  form.addEventListener('submit', function (e) {
    const u = document.getElementById('usernameOrEmail').value.trim();
    const p = document.getElementById('password').value;
    const errs = [];
    if (!u) errs.push('Please enter your username or email.');
    if (!p) errs.push('Please enter your password.');
    if (errs.length) {
      e.preventDefault();
      errorBox.textContent = errs.join(' ');
      errorBox.style.display = '';
    }
  });
</script>

</body>
</html>
