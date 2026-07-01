<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forgot Password - ETMS</title>
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
      <li><a href="employee-login.php">Employee Login</a></li>
    </ul>
  </nav>
</header>

<section class="login-section">
  <h2>Forgot Password</h2>

  <?php
  if (isset($_SESSION['error'])) {
      echo "<p style='color:red; text-align:center;'>{$_SESSION['error']}</p>";
      unset($_SESSION['error']);
  }
  if (isset($_SESSION['success'])) {
      echo "<p style='color:green; text-align:center;'>{$_SESSION['success']}</p>";
      unset($_SESSION['success']);
  }
  ?>

  <form id="forgotForm" action="forgot-password-process.php" method="POST" novalidate>
    <label for="email">Enter your registered Email</label>
    <input type="email" id="email" name="email" placeholder="Email address" required />

    <button type="submit">Send Reset Link</button>
  </form>

  <div style="text-align:center; margin-top: 1rem;">
    <a href="index.php" class="back-home">← Back to Home</a> | 
    <a href="register.php">Register</a> | 
    <a href="admin-login.php">Admin Login</a> | 
    <a href="employee-login.php">Employee Login</a>
  </div>
</section>

<script>
  document.getElementById('forgotForm').addEventListener('submit', function(event) {
    const email = document.getElementById('email').value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email || !emailRegex.test(email)) {
      alert("Please enter a valid email address.");
      event.preventDefault();
    }
  });
</script>

</body>
</html>
