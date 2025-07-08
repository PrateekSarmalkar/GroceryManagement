<?php
require_once '../config/session.php';
?>
<header>
    <div class="container">
        <a href="home.php" class="logo">GS</a>
        <nav>
            <ul>
                <li><a href="home.php" <?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'class="active"' : ''; ?>>Home</a></li>
                <li><a href="cart.php" <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'class="active"' : ''; ?>>Cart</a></li>
                <li><a href="user.php" <?php echo basename($_SERVER['PHP_SELF']) == 'user.php' ? 'class="active"' : ''; ?>>Profile</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="signin.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="signin.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header> 