<?php
// modules/inventory/list.php
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container">
        <div class="card-header">
            <h1 class="card-title">Inventory Management</h1>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Stock
            </a>
        </div>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Drug Name</th>
                            <th>Batch Number</th>
                            <th>Quantity</th>
                            <th>Expiry Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $db->query("
                            SELECT i.*, d.drug_name 
                            FROM inventory i
                            JOIN drugs d ON i.drug_id = d.drug_id
                            ORDER BY i.expiry_date ASC
                        ");
                        
                        while($row = $result->fetch_assoc()):
                            $days_until_expiry = (strtotime($row['expiry_date']) - time()) / (60 * 60 * 24);
                        ?>
                        <tr>
                            <td><?php echo $row['inventory_id']; ?></td>
                            <td><strong><?php echo $row['drug_name']; ?></strong></td>
                            <td><?php echo $row['batch_number']; ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['expiry_date'])); ?></td>
                            <td><?php echo $row['location'] ?: 'N/A'; ?></td>
                            <td>
                                <?php if($row['quantity'] == 0): ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php elseif($days_until_expiry <= 0): ?>
                                    <span class="badge badge-danger">Expired</span>
                                <?php elseif($days_until_expiry <= 30): ?>
                                    <span class="badge badge-warning">Expiring Soon</span>
                                <?php else: ?>
                                    <span class="badge badge-success">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $row['inventory_id']; ?>" class="btn btn-secondary" style="padding: 0.25rem 0.75rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>