<?php
require_once '../config/db.php';
require_once '../config/session.php';

if (!isset($_SESSION['seller_id'])) {
    header("Location: seller_signin.php");
    exit();
}

$seller_id = $_SESSION['seller_id'];

// Delete products with zero stock
try {
    // Start transaction
    $pdo->beginTransaction();
    
    // First, delete any cart items referencing these products
    $stmt = $pdo->prepare("DELETE FROM cart WHERE product_id IN (SELECT id FROM products WHERE seller_id = ? AND stock = 0)");
    $stmt->execute([$seller_id]);
    
    // Then delete order items referencing these products
    $stmt = $pdo->prepare("DELETE FROM order_items WHERE product_id IN (SELECT id FROM products WHERE seller_id = ? AND stock = 0)");
    $stmt->execute([$seller_id]);
    
    // Finally delete the products
    $stmt = $pdo->prepare("DELETE FROM products WHERE seller_id = ? AND stock = 0");
    $stmt->execute([$seller_id]);
    
    // Commit transaction
    $pdo->commit();
} catch(PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    $error = "Error deleting products: " . $e->getMessage();
}

// Handle product addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $weight = $_POST['weight'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];
    $image_url = $_POST['image_url'];
    $error = '';
    if (empty($image_url)) {
        $error = 'Please provide an image URL.';
    }
    if (empty($error)) {
    try {
        $sql = "INSERT INTO products (seller_id, name, price, weight, image_url, description, category, stock) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$seller_id, $name, $price, $weight, $image_url, $description, $category, $stock]);
        $success = "Product added successfully!";
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // First delete cart items referencing this product
        $stmt = $pdo->prepare("DELETE FROM cart WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        // Then delete order items referencing this product
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        // Finally delete the product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$product_id, $seller_id]);
        
        // Commit transaction
        $pdo->commit();
        $success = "Product deleted successfully";
    } catch(PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error = "Error deleting product: " . $e->getMessage();
    }
}

// Handle product update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $weight = $_POST['weight'];
    $image_url = $_POST['image_url'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];

    try {
        $sql = "UPDATE products SET name = ?, price = ?, weight = ?, image_url = ?, description = ?, category = ?, stock = ? 
                WHERE id = ? AND seller_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $price, $weight, $image_url, $description, $category, $stock, $product_id, $seller_id]);
        $success = "Product updated successfully!";
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = strtolower($_POST['new_status']);
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        if ($new_status === 'delivered') {
            // Get all items in the order
            $stmt = $pdo->prepare("SELECT oi.product_id, oi.quantity 
                                 FROM order_items oi 
                                 WHERE oi.order_id = ?");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll();
            
            // Update stock for each product
            foreach ($order_items as $item) {
                $stmt = $pdo->prepare("UPDATE products 
                                     SET stock = stock - ? 
                                     WHERE id = ? AND seller_id = ?");
                $stmt->execute([$item['quantity'], $item['product_id'], $seller_id]);
                
                // Delete product if stock becomes 0
                $stmt = $pdo->prepare("DELETE FROM products 
                                     WHERE id = ? AND seller_id = ? AND stock <= 0");
                $stmt->execute([$item['product_id'], $seller_id]);
            }
        }
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND seller_id = ?");
        $stmt->execute([$new_status, $order_id, $seller_id]);
        
        // Commit transaction
        $pdo->commit();
        $success = "Order status updated successfully!";
    } catch(PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error = "Error updating order status: " . $e->getMessage();
    }
}

// Get seller's products
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
    $stmt->execute([$seller_id]);
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error loading products: " . $e->getMessage();
}

// Get seller's orders
try {
    $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, 
                          (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as item_count 
                          FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          WHERE o.seller_id = ? 
                          ORDER BY o.created_at DESC");
    $stmt->execute([$seller_id]);
    $orders = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error loading orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Grocery Store</title>
    <link rel="stylesheet" href="..\css\home.css">
    <link rel="stylesheet" href="..\css\seller.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <a href="seller_dashboard.php" class="logo">GS</a>
            <nav>
                <ul>
                    <li><a href="seller_dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="signin.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section id="dashboard-banner">
            <div class="container">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['seller_name']); ?></h1>
                <p>Manage your products</p>
            </div>
        </section>

        <section id="dashboard-content">
            <div class="container">
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <h2>Add New Product</h2>
                        <form method="POST" action="" class="add-product-form">
                            <input type="text" name="name" placeholder="Product Name" required>
                            <input type="number" name="price" placeholder="Price" step="0.01" required>
                            <input type="text" name="weight" placeholder="Weight" required>
                            <input type="text" name="image_url" placeholder="Image URL" required>
                            <textarea name="description" placeholder="Description" required></textarea>
                            <input type="text" name="category" placeholder="Category" required>
                            <input type="number" name="stock" placeholder="Stock" required>
                            <button type="submit" name="add_product" class="btn-primary">Add Product</button>
                        </form>
                    </div>

                    <div class="dashboard-card">
                        <h2>Your Products</h2>
                        <div class="products-list">
                            <?php foreach ($products as $product): ?>
                                <div class="product-item" data-id="<?php echo $product['id']; ?>" 
                                     data-weight="<?php echo htmlspecialchars($product['weight']); ?>"
                                     data-image-url="<?php echo htmlspecialchars($product['image_url']); ?>"
                                     data-description="<?php echo htmlspecialchars($product['description']); ?>"
                                     data-category="<?php echo htmlspecialchars($product['category']); ?>">
                                    <div class="product-image">
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </div>
                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <div class="product-meta">
                                            <span class="price">Rs. <?php echo number_format($product['price'], 2); ?></span>
                                            <span class="stock">Stock: <?php echo $product['stock']; ?></span>
                                        </div>
                                        <div class="product-actions">
                                            <button class="edit-btn" onclick="editProduct(<?php echo $product['id']; ?>)">Edit</button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" name="delete_product" class="delete-btn" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <h2>Recent Orders</h2>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <div class="order-item" data-order-id="<?php echo $order['id']; ?>">
                                    <h3>Order #<?php echo $order['id']; ?></h3>
                                    <p>Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p>Items: <?php echo $order['item_count'] ?? 0; ?></p>
                                    <p>Total: Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
                                    <div class="order-status-container">
                                        <p>Status: <span class="order-status <?php echo strtolower($order['status']); ?>" id="order-status-<?php echo $order['id']; ?>">
                                            <?php 
                                                $status = strtolower(trim($order['status']));
                                                echo $status === 'delivered' ? 'Delivered' : 'Pending';
                                            ?>
                                        </span></p>
                                        <?php if (strtolower($order['status']) == 'pending'): ?>
                                            <form method="POST" class="status-form" onsubmit="return markDelivered(event, <?php echo $order['id']; ?>);">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="new_status" value="delivered">
                                                <button type="submit" name="update_status" class="btn btn-primary">
                                                    <i class="fas fa-check"></i> Mark as Delivered
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Product</h3>
                    <ul>
                        <li><a href="#">Grocery</a></li>
                        <li><a href="#">Popular</a></li>
                        <li><a href="#">New</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <ul>
                        <li>Phone: +91 123456789</li>
                        <li>Email: prateek@gmail.com</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
    function editProduct(productId) {
        const product = document.querySelector(`.product-item[data-id="${productId}"]`);
        const currentName = product.querySelector('h3').textContent;
        const currentPrice = product.querySelector('.price').textContent.replace('Rs. ', '');
        const currentStock = product.querySelector('.stock').textContent.replace('Stock: ', '');
        
        // Get the current product data from data attributes
        const currentWeight = product.dataset.weight;
        const currentImageUrl = product.dataset.imageUrl;
        const currentDescription = product.dataset.description;
        const currentCategory = product.dataset.category;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="product_id" value="${productId}">
            <input type="text" name="name" value="${currentName}" placeholder="Product Name" required>
            <input type="number" name="price" value="${currentPrice}" step="0.01" placeholder="Price" required>
            <input type="text" name="weight" value="${currentWeight}" placeholder="Weight" required>
            <input type="text" name="image_url" value="${currentImageUrl}" placeholder="Image URL" required>
            <textarea name="description" placeholder="Description" required>${currentDescription}</textarea>
            <input type="text" name="category" value="${currentCategory}" placeholder="Category" required>
            <input type="number" name="stock" value="${currentStock}" placeholder="Stock" required>
            <button type="submit" name="update_product" class="btn-primary">Update</button>
        `;
        
        product.querySelector('.product-info').innerHTML = '';
        product.querySelector('.product-info').appendChild(form);
    }

    function markDelivered(event, orderId) {
        // Instantly update the UI to show Delivered and hide the button
        var statusSpan = document.getElementById('order-status-' + orderId);
        if (statusSpan) {
            statusSpan.textContent = 'Delivered';
            statusSpan.classList.remove('pending');
            statusSpan.classList.add('delivered');
        }
        // Hide the form/button
        var form = event.target.closest('form');
        if (form) {
            form.style.display = 'none';
        }
        // Let the form submit normally
        return true;
    }
    </script>
</body>
</html> 