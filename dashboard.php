<?php
// dashboard.php
require_once 'config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="container">
        <h1 style="margin-bottom: 2rem;">Dashboard</h1>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-capsules"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Drugs</h3>
                    <?php
                    $result = $db->query("SELECT COUNT(*) as count FROM drugs");
                    $count = $result->fetch_assoc()['count'];
                    ?>
                    <div class="stat-value"><?php echo $count; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Stock Items</h3>
                    <?php
                    $result = $db->query("SELECT SUM(quantity) as total FROM inventory");
                    $total = $result->fetch_assoc()['total'] ?? 0;
                    ?>
                    <div class="stat-value"><?php echo number_format($total); ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-prescription"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Prescriptions</h3>
                    <?php
                    $result = $db->query("SELECT COUNT(*) as count FROM prescriptions WHERE status='active'");
                    $count = $result->fetch_assoc()['count'];
                    ?>
                    <div class="stat-value"><?php echo $count; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3>Expiring Soon</h3>
                    <?php
                    $result = $db->query("SELECT COUNT(*) as count FROM inventory WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
                    $count = $result->fetch_assoc()['count'];
                    ?>
                    <div class="stat-value"><?php echo $count; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Recent Transactions and Expiry Alerts -->
        <div class="stats-grid" style="grid-template-columns: 2fr 1fr;">
            <!-- Recent Transactions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Transactions</h3>
                    <a href="modules/transactions/list.php" class="btn btn-secondary">View All</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction #</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $db->query("
                                SELECT t.*, d.drug_name 
                                FROM transactions t
                                LEFT JOIN inventory i ON t.inventory_id = i.inventory_id
                                LEFT JOIN drugs d ON i.drug_id = d.drug_id
                                ORDER BY t.transaction_date DESC 
                                LIMIT 5
                            ");
                            while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $row['transaction_number']; ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $row['transaction_type'] == 'issue' ? 'danger' : 
                                            ($row['transaction_type'] == 'return' ? 'success' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($row['transaction_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['transaction_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Expiry Alerts -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Expiry Alerts</h3>
                    <a href="modules/reports/expiry_alert.php" class="btn btn-secondary">View All</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Drug</th>
                                <th>Batch</th>
                                <th>Expiry</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $db->query("
                                SELECT i.*, d.drug_name 
                                FROM inventory i
                                JOIN drugs d ON i.drug_id = d.drug_id
                                WHERE i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                                ORDER BY i.expiry_date ASC
                                LIMIT 5
                            ");
                            while($row = $result->fetch_assoc()):
                            $days_until_expiry = (strtotime($row['expiry_date']) - time()) / (60 * 60 * 24);
                            ?>
                            <tr>
                                <td><?php echo $row['drug_name']; ?></td>
                                <td><?php echo $row['batch_number']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['expiry_date'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $days_until_expiry <= 30 ? 'danger' : 'warning'; 
                                    ?>">
                                        <?php echo round($days_until_expiry); ?> days
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Stock Overview -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3 class="card-title">Stock Overview</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Drug Name</th>
                            <th>Batch Number</th>
                            <th>Quantity</th>
                            <th>Expiry Date</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $db->query("
                            SELECT i.*, d.drug_name 
                            FROM inventory i
                            JOIN drugs d ON i.drug_id = d.drug_id
                            ORDER BY i.expiry_date ASC
                            LIMIT 10
                        ");
                        while($row = $result->fetch_assoc()):
                        $expiry_status = (strtotime($row['expiry_date']) - time()) / (60 * 60 * 24);
                        ?>
                        <tr>
                            <td><?php echo $row['drug_name']; ?></td>
                            <td><?php echo $row['batch_number']; ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['expiry_date'])); ?></td>
                            <td><?php echo $row['location']; ?></td>
                            <td>
                                <?php if($row['quantity'] == 0): ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php elseif($expiry_status <= 30): ?>
                                    <span class="badge badge-warning">Expiring Soon</span>
                                <?php else: ?>
                                    <span class="badge badge-success">In Stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>