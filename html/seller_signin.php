<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            $sql = "SELECT * FROM sellers WHERE email = ? AND password = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email, $password]);
            
            if ($stmt->rowCount() > 0) {
                $seller = $stmt->fetch();
                session_start();
                $_SESSION['seller_id'] = $seller['id'];
                $_SESSION['seller_name'] = $seller['name'];
                header("Location: seller_dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Sign In</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <section class="login">
        <div class="textbox">
            <div class="head">
                <h1>Seller Sign In</h1>
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
                <a href="seller_signup.php">Sign Up</a>
            </p>
            <div class="divider">
                <span>OR</span>
            </div>
            <p class="account-text">
                <a href="signin.php">Sign in as Customer</a>
            </p>
        </div>
    </section>
</body>
</html> 