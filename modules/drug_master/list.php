<?php
// modules/drug_master/list.php
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Handle Delete
if(isset($_GET['delete'])) {
    $id = $db->real_escape_string($_GET['delete']);
    $db->query("DELETE FROM drugs WHERE drug_id = $id");
    header("Location: list.php?msg=deleted");
    exit();
}
?>

<div class="main-content">
    <div class="container">
        <div class="card-header">
            <h1 class="card-title">Drug Master List</h1>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Drug
            </a>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                if($_GET['msg'] == 'added') echo "Drug added successfully!";
                if($_GET['msg'] == 'updated') echo "Drug updated successfully!";
                if($_GET['msg'] == 'deleted') echo "Drug deleted successfully!";
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Drug Name</th>
                            <th>Generic Name</th>
                            <th>Category</th>
                            <th>Manufacturer</th>
                            <th>Unit Price</th>
                            <th>Stock Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $db->query("
                            SELECT d.*, 
                                   COALESCE(SUM(i.quantity), 0) as total_stock,
                                   MIN(i.expiry_date) as earliest_expiry
                            FROM drugs d
                            LEFT JOIN inventory i ON d.drug_id = i.drug_id
                            GROUP BY d.drug_id
                            ORDER BY d.drug_id DESC
                        ");
                        
                        while($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $row['drug_id']; ?></td>
                            <td><strong><?php echo $row['drug_name']; ?></strong></td>
                            <td><?php echo $row['generic_name']; ?></td>
                            <td><?php echo $row['category']; ?></td>
                            <td><?php echo $row['manufacturer']; ?></td>
                            <td>$<?php echo number_format($row['unit_price'], 2); ?></td>
                            <td>
                                <?php if($row['total_stock'] > 100): ?>
                                    <span class="badge badge-success">In Stock (<?php echo $row['total_stock']; ?>)</span>
                                <?php elseif($row['total_stock'] > 0): ?>
                                    <span class="badge badge-warning">Low Stock (<?php echo $row['total_stock']; ?>)</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $row['drug_id']; ?>" class="btn btn-secondary" style="padding: 0.25rem 0.75rem;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $row['drug_id']; ?>" class="btn btn-secondary" style="padding: 0.25rem 0.75rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?php echo $row['drug_id']; ?>" 
                                   class="btn btn-danger" 
                                   style="padding: 0.25rem 0.75rem;"
                                   onclick="return confirm('Are you sure you want to delete this drug?')">
                                    <i class="fas fa-trash"></i>
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