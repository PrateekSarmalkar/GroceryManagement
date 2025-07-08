<?php
require_once '../config/db.php';
require_once '../config/session.php';

if (!isLoggedIn()) {
    header("Location: signin.php");
    exit();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'grocery_store');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Check stock
    $stmt = mysqli_prepare($conn, "SELECT stock FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $stock);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($stock < $quantity) {
        $error = "Sorry, only $stock items available in stock";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($stmt, "iii", $quantity, $user_id, $product_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Handle item removal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_item'])) {
    $product_id = $_POST['product_id'];

    $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Get cart items
$sql = "SELECT c.*, p.name, p.price, p.image_url, p.stock, p.seller_id 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND p.stock > 0";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$cart_items = [];
$total = 0;
while ($item = mysqli_fetch_assoc($result)) {
    $cart_items[] = $item;
    $total += $item['price'] * $item['quantity'];
}
mysqli_stmt_close($stmt);

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    mysqli_begin_transaction($conn);

    try {
        // Check stock for all items
        foreach ($cart_items as $item) {
            if ($item['stock'] < $item['quantity']) {
                throw new Exception("Sorry, only {$item['stock']} items available for {$item['name']}");
            }
        }

        // Create order
        $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, seller_id, total_amount, status) VALUES (?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($stmt, "iid", $user_id, $cart_items[0]['seller_id'], $total);
        mysqli_stmt_execute($stmt);
        $order_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Add order items and update stock
        foreach ($cart_items as $item) {
            // Add to order_items
            $stmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Update product stock
            $stmt = mysqli_prepare($conn, "UPDATE products SET stock = stock - ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $item['quantity'], $item['product_id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Clear cart
        $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        $success = "Order placed successfully!";
        header("Location: user.php");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Grocery Store</title>
    <link rel="stylesheet" href="..\css\cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="cart-main">
        <div class="container">
            <h1>Your Shopping Cart</h1>
            
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any items yet</p>
                    <a href="home.php" class="btn">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div class="cart-content">
                    <div class="cart-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <div class="price">Rs. <?php echo number_format($item['price'], 2); ?></div>
                                    
                                    <form method="POST" class="quantity-form">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <div class="quantity-controls">
                                            <button type="button" class="qty-btn minus">-</button>
                                            <input type="number" 
                                                   name="quantity" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   max="<?php echo $item['stock']; ?>">
                                            <button type="button" class="qty-btn plus">+</button>
                                        </div>
                                        <button type="submit" name="update_quantity" class="btn">Update</button>
                                    </form>
                                    
                                    <form method="POST" class="remove-form">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" name="remove_item" class="btn remove-btn">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-summary">
                        <h2>Order Summary</h2>
                        <div class="summary-row">
                            <span>Total Items:</span>
                            <span><?php echo count($cart_items); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Total Amount:</span>
                            <span>Rs. <?php echo number_format($total, 2); ?></span>
                        </div>
                        <form method="POST">
                            <button type="submit" name="checkout" class="btn checkout-btn">
                                <i class="fas fa-lock"></i> Proceed to Checkout
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="..\js\cart.js"></script>

    <script>
        // Handle quantity buttons
        document.querySelectorAll('.qty-btn').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input[type="number"]');
                const currentValue = parseInt(input.value);
                
                if (this.classList.contains('minus') && currentValue > 1) {
                    input.value = currentValue - 1;
                } else if (this.classList.contains('plus')) {
                    input.value = currentValue + 1;
                }
            });
        });
    </script>
</body>
</html> 