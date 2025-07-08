<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $password]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Start session and redirect
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            header("Location: home.php");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <link rel="stylesheet" href="..\css\style.css">
</head>
<body>
    <section class="login">
        <div class="textbox">
            <div class="head">
                <h1>Sign In</h1>
                <h3>or use your E-mail ID</h3>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="email" name="email" placeholder="EMAIL" required>
                <input type="password" name="password" placeholder="PASSWORD" required>
                <button type="submit" class="sign_in_btn">SIGN IN</button>
            </form>
            
            <p class="account-text">Don't have an account? 
                <a href="signup.php">Sign Up</a>
            </p>
            
            <div class="divider">
                <span>OR</span>
            </div>
            
            <p class="account-text">
                <a href="seller_signin.php">Sign in as Seller</a>
            </p>
        </div>
    </section>
</body>
</html> 