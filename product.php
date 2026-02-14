<?php
require_once 'header.php';
require_once 'database.php';

$db = new Database();
$conn = $db->getConnection();

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search OR p.manufacturer LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($category > 0) {
    $query .= " AND p.category_id = :category";
    $params[':category'] = $category;
}

$query .= " ORDER BY p.name";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, SQLITE3_TEXT);
}

$result = $stmt->execute();
$products = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $products[] = $row;
}

// Get categories for filter
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = [];
while ($row = $categories_result->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $row;
}
?>

<div class="card">
    <h1 class="card-title">Our Products</h1>
    
    <form method="GET" style="margin-bottom: 2rem;">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div style="flex: 2;">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            <div style="flex: 1;">
                <select name="category" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="0">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="products.php" class="btn btn-secondary">Clear</a>
            </div>
        </div>
    </form>
    
    <?php if(empty($products)): ?>
        <div class="alert alert-info">No products found matching your criteria.</div>
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
                        <div style="color: #666; font-size: 0.9rem;">Category: <?php echo htmlspecialchars($product['category_name']); ?></div>
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