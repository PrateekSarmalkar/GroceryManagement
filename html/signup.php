<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];

    // Simple validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            // Check if email exists
            $check = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $check->execute([$email]);
            
            if ($check->rowCount() > 0) {
                $error = "Email already exists";
            } else {
                // Insert new user
                $sql = "INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $email, $password, $phone]);
                
                // Start session and redirect
                session_start();
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                
                header("Location: home.php");
                exit();
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
    <title>Sign Up</title>
    <link rel="stylesheet" href="..\css\style.css">
</head>
<body>
    <section class="signup">
        <div class="textbox">
            <div class="head">
                <h1>Create Account</h1>
                <h3>or use your E-mail ID</h3>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="text" name="name" placeholder="NAME" required>
                <input type="email" name="email" placeholder="EMAIL" required>
                <input type="password" name="password" placeholder="PASSWORD" required>
                <input type="text" name="phone" placeholder="PHONE NO. (optional)">
                <button type="submit" class="sign_up_btn">SIGN UP AS CUSTOMER</button>
            </form>
            
            <div class="divider">
                <span>OR</span>
            </div>
            
            <a href="seller_signup.php" class="seller-signup-btn">SIGN UP AS SELLER</a>
            
            <p class="account-text">Already have an account? 
                <a href="signin.php">Sign In</a>
            </p>
        </div>
    </section>
</body>
</html>
