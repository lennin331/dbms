<?php
require_once 'header.php';
require_once 'database.php';

$db = new Database();
$conn = $db->getConnection();

// Get featured products
$result = $conn->query("SELECT * FROM products ORDER BY RANDOM() LIMIT 6");
$featured_products = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $featured_products[] = $row;
}

// Get categories
$result = $conn->query("SELECT * FROM categories LIMIT 4");
$categories = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $row;
}
?>

<div class="card">
    <h1 class="card-title">Welcome to PharmaCare</h1>
    <p style="color: #666; line-height: 1.6;">Your trusted online pharmacy for quality medications and healthcare products. We provide authentic medicines with convenient home delivery.</p>
</div>

<div class="card">
    <h2 class="card-title">Featured Products</h2>
    <div class="grid">
        <?php foreach($featured_products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="small.webp" width="300" height="200" alt="">
                </div>
                <div class="product-info">
                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                    <div class="product-stock <?php echo $product['stock'] < 10 ? 'low' : ''; ?>">
                        Stock: <?php echo $product['stock']; ?> units
                    </div>
                    <div class="product-manufacturer">By: <?php echo htmlspecialchars($product['manufacturer']); ?></div>
                    <?php if($product['prescription_required']): ?>
                        <div style="color: #f56565; font-size: 0.9rem;">Prescription Required</div>
                    <?php endif; ?>
                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">View Details</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h2 class="card-title">Shop by Category</h2>
    <div class="grid">
        <?php foreach($categories as $category): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="big.webp" height="200" width="280" alt="Product Image">
                </div>
                <div class="product-info">
                    <div class="product-name"><?php echo htmlspecialchars($category['name']); ?></div>
                    <div class="product-manufacturer"><?php echo htmlspecialchars($category['description']); ?></div>
                    <a href="category.php?id=<?php echo $category['id']; ?>" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Browse Category</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h2 class="card-title">Why Choose Us?</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
        <div style="text-align: center;">
            <div style="font-size: 3rem; wW: #667eea;">✓</div>
            <h3>100% Authentic</h3>
            <p style="color: #666;">All medications are sourced directly from licensed manufacturers</p>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 3rem; color: #667eea;">⚡</div>
            <h3>Fast Delivery</h3>
            <p style="color: #666;">Same day delivery in select areas</p>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 3rem; color: #667eea;">🔒</div>
            <h3>Secure Payment</h3>
            <p style="color: #666;">Your transactions are always safe and encrypted</p>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 3rem; color: #667eea;">👨‍⚕️</div>
            <h3>Expert Support</h3>
            <p style="color: #666;">Consult with our licensed pharmacists</p>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>