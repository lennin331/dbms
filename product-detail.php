<?php
require_once 'header.php';
require_once 'database.php';

$db = new Database();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();
$product = $result->fetchArray(SQLITE3_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}
?>

<div class="card">
    <a href="products.php" class="btn btn-secondary" style="margin-bottom: 1rem;">← Back to Products</a>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 400px; border-radius: 10px;">
                <span style="font-size: 8rem;"><img src="big2.webp" width="600" height="450" alt=""></span>
            </div>
        </div>
        <div>
            <h1 style="color: #333; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($product['name']); ?></h1>
            <p style="color: #667eea; font-size: 2rem; font-weight: bold; margin-bottom: 1rem;">$<?php echo number_format($product['price'], 2); ?></p>
            
            <div style="margin-bottom: 2rem;">
                <p style="color: #666; line-height: 1.6; margin-bottom: 1rem;"><?php echo htmlspecialchars($product['description']); ?></p>
                
                <table style="width: 100%;">
                    <tr>
                        <th style="background: #f7fafc; color: #333;">Manufacturer</th>
                        <td><?php echo htmlspecialchars($product['manufacturer']); ?></td>
                    </tr>
                    <tr>
                        <th style="background: #f7fafc; color: #333;">Category</th>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                    </tr>
                    <tr>
                        <th style="background: #f7fafc; color: #333;">Stock Status</th>
                        <td>
                            <?php if($product['stock'] > 0): ?>
                                <span style="color: #48bb78;">In Stock (<?php echo $product['stock']; ?> units)</span>
                            <?php else: ?>
                                <span style="color: #f56565;">Out of Stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th style="background: #f7fafc; color: #333;">Expiry Date</th>
                        <td><?php echo date('F d, Y', strtotime($product['expiry_date'])); ?></td>
                    </tr>
                    <tr>
                        <th style="background: #f7fafc; color: #333;">Prescription Required</th>
                        <td>
                            <?php if($product['prescription_required']): ?>
                                <span style="color: #f56565;">Yes</span>
                            <?php else: ?>
                                <span style="color: #48bb78;">No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($product['stock'] > 0): ?>
                    <?php if($product['prescription_required']): ?>
                        <div class="alert alert-info" style="margin-bottom: 1rem;">
                            This product requires a prescription. Please upload your prescription during checkout.
                        </div>
                    <?php endif; ?>
                    
                    <form action="add-to-cart.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="form-group">
                            <label for="quantity">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" min="1" max="<?php echo $product['stock']; ?>" value="1" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Add to Cart</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-error">This product is currently out of stock.</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    Please <a href="login.php">login</a> to purchase this product.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <h2 class="card-title">Product Information</h2>
    
    <div style="margin-top: 1rem;">
        <h3 style="color: #333; margin-bottom: 0.5rem;">Usage Instructions</h3>
        <p style="color: #666; line-height: 1.6; margin-bottom: 1rem;">Please follow the dosage instructions provided by your healthcare provider. Do not exceed the recommended dosage without consulting your doctor.</p>
        
        <h3 style="color: #333; margin-bottom: 0.5rem;">Storage Instructions</h3>
        <p style="color: #666; line-height: 1.6; margin-bottom: 1rem;">Store in a cool, dry place away from direct sunlight. Keep out of reach of children.</p>
        
        <h3 style="color: #333; margin-bottom: 0.5rem;">Warnings</h3>
        <p style="color: #666; line-height: 1.6; margin-bottom: 1rem;">If you are pregnant, nursing, taking other medications, or have a medical condition, consult your doctor before using this product.</p>
    </div>
</div>

<?php require_once 'footer.php'; ?>