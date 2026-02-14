<?php
// modules/drug_master/view.php
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$id = $_GET['id'] ?? 0;
$result = $db->query("SELECT * FROM drugs WHERE drug_id = $id");
$drug = $result->fetch_assoc();

if(!$drug) {
    header("Location: list.php");
    exit();
}

// Get inventory details
$inventory = $db->query("
    SELECT * FROM inventory 
    WHERE drug_id = $id 
    ORDER BY expiry_date ASC
");
?>

<div class="main-content">
    <div class="container">
        <div class="card-header">
            <h1 class="card-title">Drug Details: <?php echo $drug['drug_name']; ?></h1>
            <div>
                <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
            <div class="card">
                <h3 style="margin-bottom: 1rem;">Basic Information</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 0.5rem; color: var(--text-secondary);">Drug ID:</td>
                        <td style="padding: 0.5rem;"><strong><?php echo $drug['drug_id']; ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem; color: var(--text-secondary);">Drug Name:</td>
                        <td style="padding: 0.5rem;"><?php echo $drug['drug_name']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem; color: var(--text-secondary);">Generic Name:</td>
                        <td style="padding: 0.5rem;"><?php echo $drug['generic_name'] ?: 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem; color: var(--text-secondary);">Category:</td>
                        <td style="padding: 0.5rem;"><?php echo $drug['category']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem; color: var(--text-secondary);">Manufacturer:</td>
                        <td style="padding: 0.5rem;"><?php echo $drug['manufacturer'] ?: 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem; color: var(--text-secondary);">Unit Price:</td>
                        <td style="padding: 0.5rem;">$<?php echo number_format($drug['unit_price'], 2); ?></td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h3 style="margin-bottom: 1rem;">Stock Summary</h3>
                <?php
                $stock_summary = $db->query("
                    SELECT 
                        SUM(quantity) as total_quantity,
                        COUNT(*) as total_batches,
                        MIN(expiry_date) as earliest_expiry,
                        MAX(expiry_date) as latest_expiry
                    FROM inventory 
                    WHERE drug_id = $id
                ")->fetch_assoc();
                ?>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 0.5rem; color: var(--text-secondary);">Total Quantity:</td>
                        <td style="padding: 0.5rem;"><strong><?php echo $stock_summary['total_quantity'] ?: 0; ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem; color: var(--text-secondary);">Total Batches:</td>
                        <td style="padding: 0.5rem;"><?php echo $stock_summary['total_batches'] ?: 0; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem; color: var(--text-secondary);">Earliest Expiry:</td>
                        <td style="padding: 0.5rem;">
                            <?php 
                            if($stock_summary['earliest_expiry']) {
                                $days = (strtotime($stock_summary['earliest_expiry']) - time()) / (60 * 60 * 24);
                                echo date('Y-m-d', strtotime($stock_summary['earliest_expiry']));
                                if($days <= 30) {
                                    echo ' <span class="badge badge-warning">Expiring soon</span>';
                                }
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card" style="margin-top: 2rem;">
            <h3 class="card-title">Inventory Batches</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Batch Number</th>
                            <th>Quantity</th>
                            <th>Manufacturing Date</th>
                            <th>Expiry Date</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($inventory->num_rows > 0): ?>
                            <?php while($batch = $inventory->fetch_assoc()): 
                                $days_until_expiry = (strtotime($batch['expiry_date']) - time()) / (60 * 60 * 24);
                            ?>
                            <tr>
                                <td><?php echo $batch['batch_number']; ?></td>
                                <td><?php echo $batch['quantity']; ?></td>
                                <td><?php echo $batch['mfg_date'] ? date('Y-m-d', strtotime($batch['mfg_date'])) : 'N/A'; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($batch['expiry_date'])); ?></td>
                                <td><?php echo $batch['location'] ?: 'N/A'; ?></td>
                                <td>
                                    <?php if($batch['quantity'] == 0): ?>
                                        <span class="badge badge-danger">Out of Stock</span>
                                    <?php elseif($days_until_expiry <= 0): ?>
                                        <span class="badge badge-danger">Expired</span>
                                    <?php elseif($days_until_expiry <= 30): ?>
                                        <span class="badge badge-warning">Expiring Soon</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No inventory records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>