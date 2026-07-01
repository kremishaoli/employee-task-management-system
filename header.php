<?php
session_start();
include 'flash.php';
?>
<header>
    <h2>ETMS</h2>
    <?php if ($msg = flash('success')): ?>
        <p class="success-msg"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
        <p class="error-msg"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>
</header>
