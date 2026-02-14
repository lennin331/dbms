<?php
// modules/reports/expiry_alert.php
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$filter = $_GET['filter'] ?? '30';
?>

<div class="main-content">
    <div class="container">
        <div class="card-header">
            <h1 class="card-title">Expiry Alerts Report</h1>
            <div>
                <a href="?filter=30" class="btn <?php echo $filter == '30' ? 'btn-primary' : 'btn-secondary'; ?>">30 Days</a>
                <a href="?filter=60" class="btn <?php echo $filter == '60' ? 'btn-primary' : 'btn-secondary'; ?>">60 Days</a>
                <a href="?filter=90" class="btn <?php echo $filter == '90' ? 'btn-primary' : 'btn-secondary'; ?>">90 Days</a>
                <a href="?filter=expired" class="btn <?php echo $filter == 'expired' ? 'btn-primary' : 'btn-secondary'; ?>">Expired</a>
            </div>
        </div>

        <div class="card">
            <?php
            if($filter == 'expired') {
                $query = "
                    SELECT i.*, d.drug_name, 
                           DATEDIFF(i.expiry_date, CURDATE()) as days_until_expiry
                    FROM inventory i
                    JOIN drugs d ON i.drug_id = d.drug_id
                    WHERE i.expiry_date < CURDATE()
                    ORDER BY i.expiry_date ASC
                ";
            } else {
                $days = intval($filter);
                $query = "
                    SELECT i.*, d.drug_name,
                           DATEDIFF(i.expiry_date, CURDATE()) as days_until_expiry
                    FROM inventory i
                    JOIN drugs d ON i.drug_id = d.drug_id
                    WHERE i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY)
                    ORDER BY i.expiry_date ASC
                ";
            }
            
            $result = $db->query($query);
            ?>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Drug Name</th>
                            <th>Batch Number</th>
                            <th>Quantity</th>
                            <th>Expiry Date</th>
                            <th>Days Until Expiry</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $row['drug_name']; ?></strong></td>
                                <td><?php echo $row['batch_number']; ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['expiry_date'])); ?></td>
                                <td>
                                    <?php 
                                    if($row['days_until_expiry'] < 0) {
                                        echo '<span class="badge badge-danger">Expired</span>';
                                    } else {
                                        echo $row['days_until_expiry'] . ' days';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $row['location'] ?: 'N/A'; ?></td>
                                <td>
                                    <?php if($row['days_until_expiry'] < 0): ?>
                                        <span class="badge badge-danger">Expired</span>
                                    <?php elseif($row['days_until_expiry'] <= 30): ?>
                                        <span class="badge badge-danger">Critical</span>
                                    <?php elseif($row['days_until_expiry'] <= 60): ?>
                                        <span class="badge badge-warning">Warning</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Good</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No expiring drugs found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>