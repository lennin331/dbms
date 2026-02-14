<?php
// modules/prescriptions/list.php
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container">
        <div class="card-header">
            <h1 class="card-title">Prescriptions</h1>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Prescription
            </a>
        </div>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Prescription #</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $db->query("
                            SELECT p.*, 
                                   pt.patient_name, 
                                   d.doctor_name 
                            FROM prescriptions p
                            JOIN patients pt ON p.patient_id = pt.patient_id
                            JOIN doctors d ON p.doctor_id = d.doctor_id
                            ORDER BY p.created_at DESC
                        ");
                        
                        while($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><strong><?php echo $row['prescription_number']; ?></strong></td>
                            <td><?php echo $row['patient_name']; ?></td>
                            <td><?php echo $row['doctor_name']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['prescription_date'])); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $row['status'] == 'active' ? 'success' : 
                                        ($row['status'] == 'dispensed' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $row['prescription_id']; ?>" class="btn btn-secondary" style="padding: 0.25rem 0.75rem;">
                                    <i class="fas fa-eye"></i>
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