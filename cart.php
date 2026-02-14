<?php
require_once 'header.php';
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]);
    }
    header('Location: cart.php');
    exit;
}

// Handle update quantity
if (isset($_POST['update'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    header('Location: cart.php');
    exit;
}

// Get cart items
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $result = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $row['cart_quantity'] = $_SESSION['cart'][$row['id']];
        $row['subtotal'] = $row['price'] * $row['cart_quantity'];
        $total += $row['subtotal'];
        $cart_items[] = $row;
    }
}
?>

<div class="card">
    <h1 class="card-title">Shopping Cart</h1>
    
    <?php if(empty($cart_items)): ?>
        <div class="alert alert-info">
            Your cart is empty. <a href="products.php" style="color: #667eea;">Continue shopping</a>
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div style="font-weight: bold;"><?php echo htmlspecialchars($item['name']); ?></div>
                                <small style="color: #666;"><?php echo htmlspecialchars($item['manufacturer']); ?></small>
                                <?php if($item['prescription_required']): ?>
                                    <div style="color: #f56565; font-size: 0.9rem;">Prescription Required</div>
                                <?php endif; ?>
                            </td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <input type="number" name="quantity[<?php echo $item['id']; ?>]" 
                                       value="<?php echo $item['cart_quantity']; ?>" 
                                       min="1" max="<?php echo $item['stock']; ?>"
                                       style="width: 80px; padding: 0.5rem;">
                            </td>
                            <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                            <td>
                                <a href="?remove=<?php echo $item['id']; ?>" class="btn btn-danger" 
                                   onclick="return confirm('Remove this item from cart?')">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                        <td colspan="2" style="font-weight: bold; color: #667eea;">$<?php echo number_format($total, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="display: flex; gap: 1rem; justify-content: space-between; margin-top: 1rem;">
                <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
                <div>
                    <button type="submit" name="update" class="btn btn-primary">Update Cart</button>
                    <a href="checkout.php" class="btn btn-success" style="background: #48bb78; color: white; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none;">Proceed to Checkout</a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>