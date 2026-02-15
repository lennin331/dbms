<?php
require_once 'header.php';
require_once 'database.php';

$db = new Database();
$conn = $db->getConnection();

$result = $conn->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name");
$categories = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $row;
}
?>

<div class="card">
    <h1 class="card-title">Product Categories</h1>
    
    <div class="grid">
        <?php foreach($categories as $category): ?>
            <div class="product-card">
                <div class="product-image">
                                            <img src="small.webp" height="200" width="280" alt="Product Image">

                </div>
                <div class="product-info">
                    <div class="product-name"><?php echo htmlspecialchars($category['name']); ?></div>
                    <div class="product-manufacturer" style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($category['description']); ?></div>
                    <div style="color: #667eea; font-weight: bold; margin-bottom: 0.5rem;">
                        Products: <?php echo $category['product_count']; ?>
                    </div>
                    <a href="category.php?id=<?php echo $category['id']; ?>" class="btn btn-primary" style="width: 100%;">View Products</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>