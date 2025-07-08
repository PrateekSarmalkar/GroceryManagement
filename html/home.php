<?php
require_once '../config/db.php';
require_once '../config/session.php';

// Get all unique categories for the dropdown
try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $error = "Error loading categories: " . $e->getMessage();
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        header("Location: signin.php");
        exit();
    }
    
    $product_id = $_POST['product_id'];
    $user_id = getCurrentUserId();
    
    try {
        // Check if item already exists in cart
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing_item = $stmt->fetch();
        
        if ($existing_item) {
            // Update quantity
            $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
        } else {
            // Add new item
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
            $stmt->execute([$user_id, $product_id]);
        }
        
        header("Location: home.php?success=1");
        exit();
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle search
$search = '';
$category = '';
$products = [];

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    
    try {
        $sql = "SELECT p.*, s.shop_name 
                FROM products p 
                JOIN sellers s ON p.seller_id = s.id 
                WHERE p.stock > 0";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($category)) {
            $sql .= " AND p.category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error = "Error searching products: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grocery Store</title>
    <link rel="stylesheet" href="..\css\home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section id="search-banner">
            <div class="container">
                <h1>Order Your Daily Groceries</h1>
                <p>#FreeDelivery</p>
                <form method="GET" action="" class="search-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search for groceries..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit"><i class="fas fa-search"></i> Search</button>
                    </div>
                </form>
            </div>
        </section>
        
        <section id="our-products">
            <div class="container">
                <h2>Our Products</h2>
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <i class="fas fa-search" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your search or category filter</p>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <span class="weight"><?php echo htmlspecialchars($product['weight']); ?></span>
                                    <div class="price">Rs. <?php echo number_format($product['price'], 2); ?></div>
                                    <div class="shop-name"><?php echo htmlspecialchars($product['shop_name']); ?></div>
                                    <form method="POST" action="home.php">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Auto-hide toast message after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.querySelector('.toast-message');
            if (toast) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        });
    </script>
</body>
</html> 