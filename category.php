<?php
require_once 'header.php';
require_once 'database.php';

$db = new Database();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get category info
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();
$category = $result->fetchArray(SQLITE3_ASSOC);

if (!$category) {
    header('Location: categories.php');
    exit;
}

// Get products in this category
$stmt = $conn->prepare("SELECT * FROM products WHERE category_id = :id ORDER BY name");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();
$products = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $products[] = $row;
}
?>

<div class="card">
    <a href="categories.php" class="btn btn-secondary" style="margin-bottom: 1rem;">← Back to Categories</a>
    
    <h1 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h1>
    <p style="color: #666; margin-bottom: 2rem;"><?php echo htmlspecialchars($category['description']); ?></p>
    
    <?php if(empty($products)): ?>
        <div class="alert alert-info">No products found in this category.</div>
    <?php else: ?>
        <div class="grid">
            <?php foreach($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        💊
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                        <div class="product-stock <?php echo $product['stock'] < 10 ? 'low' : ''; ?>">
                            Stock: <?php echo $product['stock']; ?> units
                        </div>
                        <div class="product-manufacturer"><?php echo htmlspecialchars($product['manufacturer']); ?></div>
                        <?php if($product['prescription_required']): ?>
                            <div style="color: #f56565; font-size: 0.9rem;">Prescription Required</div>
                        <?php endif; ?>
                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">View Details</a>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="add-to-cart.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary" style="width: 100%; margin-top: 0.5rem;">Add to Cart</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>