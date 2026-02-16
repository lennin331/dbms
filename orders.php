<?php
require_once 'header.php';
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Handle order cancellation if requested
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $cancel_id = (int)$_GET['id'];
    
    // Check if order belongs to user and is pending
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :user_id AND status = 'pending'");
    $stmt->bindValue(':id', $cancel_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $order = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($order) {
        // Begin transaction to restore stock
        $conn->exec('BEGIN TRANSACTION');
        
        try {
            // Get order items to restore stock
            $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
            $stmt->bindValue(':order_id', $cancel_id, SQLITE3_INTEGER);
            $items_result = $stmt->execute();
            
            while ($item = $items_result->fetchArray(SQLITE3_ASSOC)) {
                // Restore stock
                $stmt2 = $conn->prepare("UPDATE products SET stock = stock + :quantity WHERE id = :product_id");
                $stmt2->bindValue(':quantity', $item['quantity'], SQLITE3_INTEGER);
                $stmt2->bindValue(':product_id', $item['product_id'], SQLITE3_INTEGER);
                $stmt2->execute();
            }
            
            // Update order status
            $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = :id");
            $stmt->bindValue(':id', $cancel_id, SQLITE3_INTEGER);
            $stmt->execute();
            
            $conn->exec('COMMIT');
            $success_message = "Order #" . str_pad($cancel_id, 6, '0', STR_PAD_LEFT) . " has been cancelled successfully.";
        } catch (Exception $e) {
            $conn->exec('ROLLBACK');
            $error_message = "Failed to cancel order. Please try again.";
        }
    }
}

// Get filter parameter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get user's orders with filter
$query = "SELECT * FROM orders WHERE user_id = :user_id";
if ($status_filter !== 'all') {
    $query .= " AND status = :status";
}
$query .= " ORDER BY order_date DESC";

$stmt = $conn->prepare($query);
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
if ($status_filter !== 'all') {
    $stmt->bindValue(':status', $status_filter, SQLITE3_TEXT);
}
$result = $stmt->execute();

$orders = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $orders[] = $row;
}

// Get order counts for statistics
$stats = [];
$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
foreach ($statuses as $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id AND status = :status");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats[$status] = $row['count'];
}

$total_orders = array_sum($stats);
$total_spent = 0;
$stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE user_id = :user_id AND status != 'cancelled'");
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
$total_spent = $row['total'] ?? 0;
?>

<div class="card">
    <h1 class="card-title">My Orders</h1>
    
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <!-- Order Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1rem; border-radius: 10px; text-align: center; color: white;">
            <div style="font-size: 2rem; font-weight: bold;"><?php echo $total_orders; ?></div>
            <div>Total Orders</div>
        </div>
        
        <div style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); padding: 1rem; border-radius: 10px; text-align: center; color: white;">
            <div style="font-size: 2rem; font-weight: bold;">$<?php echo number_format($total_spent * 1.1, 2); ?></div>
            <div>Total Spent</div>
        </div>
        
        <div style="background: linear-gradient(135deg, #ecc94b 0%, #d69e2e 100%); padding: 1rem; border-radius: 10px; text-align: center; color: white;">
            <div style="font-size: 2rem; font-weight: bold;"><?php echo $stats['pending']; ?></div>
            <div>Pending</div>
        </div>
        
        <div style="background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%); padding: 1rem; border-radius: 10px; text-align: center; color: white;">
            <div style="font-size: 2rem; font-weight: bold;"><?php echo $stats['delivered']; ?></div>
            <div>Delivered</div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap; border-bottom: 2px solid #f0f0f0; padding-bottom: 1rem;">
        <a href="?status=all" class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>" style="text-decoration: none;">
            All Orders (<?php echo $total_orders; ?>)
        </a>
        <a href="?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>" style="text-decoration: none;">
            Pending (<?php echo $stats['pending']; ?>)
        </a>
        <a href="?status=processing" class="btn <?php echo $status_filter === 'processing' ? 'btn-primary' : 'btn-secondary'; ?>" style="text-decoration: none;">
            Processing (<?php echo $stats['processing']; ?>)
        </a>
        <a href="?status=shipped" class="btn <?php echo $status_filter === 'shipped' ? 'btn-primary' : 'btn-secondary'; ?>" style="text-decoration: none;">
            Shipped (<?php echo $stats['shipped']; ?>)
        </a>
        <a href="?status=delivered" class="btn <?php echo $status_filter === 'delivered' ? 'btn-primary' : 'btn-secondary'; ?>" style="text-decoration: none;">
            Delivered (<?php echo $stats['delivered']; ?>)
        </a>
        <a href="?status=cancelled" class="btn <?php echo $status_filter === 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?>" style="text-decoration: none;">
            Cancelled (<?php echo $stats['cancelled']; ?>)
        </a>
    </div>
    
    <?php if(empty($orders)): ?>
        <div class="alert alert-info" style="text-align: center; padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">📦</div>
            <h2 style="color: #333; margin-bottom: 1rem;">No orders found</h2>
            <p style="color: #666; margin-bottom: 2rem;">
                <?php if($status_filter !== 'all'): ?>
                    You don't have any <?php echo $status_filter; ?> orders.
                    <a href="?status=all" style="color: #667eea;">View all orders</a>
                <?php else: ?>
                    You haven't placed any orders yet.
                <?php endif; ?>
            </p>
            <a href="products.php" class="btn btn-primary" style="padding: 1rem 2rem;">Start Shopping</a>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; min-width: 800px;">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $order): 
                        // Get item count for this order
                        $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(quantity) as total_qty FROM order_items WHERE order_id = :order_id");
                        $stmt->bindValue(':order_id', $order['id'], SQLITE3_INTEGER);
                        $item_result = $stmt->execute();
                        $item_data = $item_result->fetchArray(SQLITE3_ASSOC);
                        $item_count = $item_data['count'];
                        $total_qty = $item_data['total_qty'];
                        
                        // Get first product image/icon
                        $stmt = $conn->prepare("SELECT p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id LIMIT 1");
                        $stmt->bindValue(':order_id', $order['id'], SQLITE3_INTEGER);
                        $product_result = $stmt->execute();
                        $first_product = $product_result->fetchArray(SQLITE3_ASSOC);
                    ?>
                        <tr>
                            <td>
                                <strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <div><?php echo date('M d, Y', strtotime($order['order_date'])); ?></div>
                                <small style="color: #666;"><?php echo date('h:i A', strtotime($order['order_date'])); ?></small>
                            </td>
                            <td>
                                <div><strong><?php echo $total_qty; ?></strong> items</div>
                                <small style="color: #666;"><?php echo $item_count; ?> product<?php echo $item_count > 1 ? 's' : ''; ?></small>
                                <?php if($first_product): ?>
                                    <div style="font-size: 0.85rem; color: #888; margin-top: 0.25rem;">
                                        <?php echo htmlspecialchars(substr($first_product['name'], 0, 30)) . (strlen($first_product['name']) > 30 ? '...' : ''); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: #667eea;">$<?php echo number_format($order['total_amount'] * 1.1, 2); ?></strong>
                                <div style="font-size: 0.85rem; color: #666;">
                                    <small>Subtotal: $<?php echo number_format($order['total_amount'], 2); ?></small>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $payment_methods = [
                                    'card' => '💳 Card',
                                    'upi' => '📱 UPI',
                                    'cod' => '💵 Cash on Delivery'
                                ];
                                echo $payment_methods[$order['payment_method']] ?? ucfirst($order['payment_method']);
                                ?>
                            </td>
                            <td>
                                <?php
                                $status_colors = [
                                    'pending' => ['bg' => '#ecc94b', 'text' => '#744210', 'label' => '⏳ Pending'],
                                    'processing' => ['bg' => '#4299e1', 'text' => 'white', 'label' => '⚙️ Processing'],
                                    'shipped' => ['bg' => '#9f7aea', 'text' => 'white', 'label' => '🚚 Shipped'],
                                    'delivered' => ['bg' => '#48bb78', 'text' => 'white', 'label' => '✅ Delivered'],
                                    'cancelled' => ['bg' => '#f56565', 'text' => 'white', 'label' => '❌ Cancelled']
                                ];
                                $color = $status_colors[$order['status']] ?? ['bg' => '#666', 'text' => 'white', 'label' => ucfirst($order['status'])];
                                ?>
                                <span style="background: <?php echo $color['bg']; ?>; color: <?php echo $color['text']; ?>; padding: 0.4rem 0.8rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 500; display: inline-block;">
                                    <?php echo $color['label']; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.9rem; text-align: center;">View Details</a>
                                    
                                    <?php if($order['status'] === 'pending'): ?>
                                        <a href="?cancel=1&id=<?php echo $order['id']; ?>" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.9rem; text-align: center;" onclick="return confirm('Are you sure you want to cancel this order? This action cannot be undone.')">Cancel Order</a>
                                    <?php endif; ?>
                                    
                                    <?php if($order['status'] === 'delivered'): ?>
                                        <a href="rate-order.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.9rem; text-align: center;">Rate Order</a>
                                    <?php endif; ?>
                                    
                                    <?php if($order['status'] === 'shipped'): ?>
                                        <a href="track-order.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.9rem; text-align: center;">Track Order</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Order Summary Cards for Mobile -->
        <div style="display: none; margin-top: 1rem;" class="mobile-cards">
            <?php foreach($orders as $order): 
                $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(quantity) as total_qty FROM order_items WHERE order_id = :order_id");
                $stmt->bindValue(':order_id', $order['id'], SQLITE3_INTEGER);
                $item_result = $stmt->execute();
                $item_data = $item_result->fetchArray(SQLITE3_ASSOC);
                $total_qty = $item_data['total_qty'];
                
                $status_colors = [
                    'pending' => ['bg' => '#ecc94b', 'text' => '#744210', 'label' => '⏳ Pending'],
                    'processing' => ['bg' => '#4299e1', 'text' => 'white', 'label' => '⚙️ Processing'],
                    'shipped' => ['bg' => '#9f7aea', 'text' => 'white', 'label' => '🚚 Shipped'],
                    'delivered' => ['bg' => '#48bb78', 'text' => 'white', 'label' => '✅ Delivered'],
                    'cancelled' => ['bg' => '#f56565', 'text' => 'white', 'label' => '❌ Cancelled']
                ];
                $color = $status_colors[$order['status']] ?? ['bg' => '#666', 'text' => 'white', 'label' => ucfirst($order['status'])];
            ?>
                <div style="background: white; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                        <span style="background: <?php echo $color['bg']; ?>; color: <?php echo $color['text']; ?>; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.8rem;">
                            <?php echo $color['label']; ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: #666; font-size: 0.9rem;">
                        <span><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                        <span><?php echo $total_qty; ?> items</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="color: #667eea;">$<?php echo number_format($order['total_amount'] * 1.1, 2); ?></strong>
                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">View</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination (simplified) -->
        <?php if(count($orders) > 10): ?>
        <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
            <a href="#" class="btn btn-secondary" style="padding: 0.5rem 1rem;">← Previous</a>
            <a href="#" class="btn btn-primary" style="padding: 0.5rem 1rem;">1</a>
            <a href="#" class="btn btn-secondary" style="padding: 0.5rem 1rem;">2</a>
            <a href="#" class="btn btn-secondary" style="padding: 0.5rem 1rem;">3</a>
            <a href="#" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Next →</a>
        </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<!-- Need Help Section -->
<div class="card" style="margin-top: 1rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="color: #333; margin-bottom: 0.5rem;">Need help with your order?</h3>
            <p style="color: #666;">Contact our customer support for any questions about your orders.</p>
        </div>
        <div>
            <a href="contact.php" class="btn btn-primary">Contact Support</a>
            <a href="faq.php" class="btn btn-secondary">View FAQ</a>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    table {
        display: none;
    }
    .mobile-cards {
        display: block !important;
    }
    
    .filter-tabs {
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 0.5rem;
    }
    
    .filter-tabs .btn {
        display: inline-block;
        float: none;
    }
}

/* Hover effects for table rows */
tbody tr:hover {
    background: #f7fafc;
    cursor: pointer;
}

/* Status badge animation */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.status-pending {
    animation: pulse 2s infinite;
}
</style>

<script>
// Make table rows clickable
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking on a button or link
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') {
                return;
            }
            
            // Find the view details link in this row
            const viewLink = this.querySelector('a[href*="order-detail.php"]');
            if (viewLink) {
                window.location.href = viewLink.href;
            }
        });
    });
});

// Auto-refresh for pending orders (every 30 seconds)
<?php if($status_filter === 'pending'): ?>
setTimeout(function() {
    location.reload();
}, 30000);
<?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>
