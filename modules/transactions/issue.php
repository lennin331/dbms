<?php
// modules/transactions/issue.php
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

function generateTransactionNumber($db) {
    $prefix = 'TXN';
    $date = date('Ymd');
    
    $result = $db->query("
        SELECT COUNT(*) as count 
        FROM transactions 
        WHERE transaction_number LIKE '$prefix$date%'
    ");
    $count = $result->fetch_assoc()['count'] + 1;
    
    return $prefix . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transaction_number = generateTransactionNumber($db);
    $prescription_id = $db->real_escape_string($_POST['prescription_id']);
    $items = $_POST['inventory_id'];
    $quantities = $_POST['quantity'];
    
    $db->begin_transaction();
    
    try {
        foreach($items as $key => $inventory_id) {
            if(!empty($inventory_id) && $quantities[$key] > 0) {
                $quantity = $db->real_escape_string($quantities[$key]);
                
                // Check available quantity
                $check = $db->query("
                    SELECT quantity FROM inventory 
                    WHERE inventory_id = $inventory_id
                ")->fetch_assoc();
                
                if($check['quantity'] < $quantity) {
                    throw new Exception("Insufficient stock for item");
                }
                
                // Insert transaction
                $sql = "INSERT INTO transactions 
                        (transaction_number, transaction_type, prescription_id, inventory_id, quantity) 
                        VALUES ('$transaction_number', 'issue', $prescription_id, $inventory_id, $quantity)";
                
                if(!$db->query($sql)) {
                    throw new Exception("Error recording transaction");
                }
                
                // Update inventory
                $update = "UPDATE inventory SET quantity = quantity - $quantity 
                          WHERE inventory_id = $inventory_id";
                
                if(!$db->query($update)) {
                    throw new Exception("Error updating inventory");
                }
                
                // Log transaction
                $log = "INSERT INTO transaction_log (transaction_id, old_quantity, new_quantity, changed_by) 
                        VALUES ({$db->insert_id}, {$check['quantity']}, {$check['quantity']} - $quantity, 'SYSTEM')";
                $db->query($log);
            }
        }
        
        // Update prescription status
        $db->query("UPDATE prescriptions SET status = 'dispensed' WHERE prescription_id = $prescription_id");
        
        $db->commit();
        $success = "Medicine issued successfully! Transaction #: $transaction_number";
        
    } catch(Exception $e) {
        $db->rollback();
        $error = $e->getMessage();
    }
}

// Get active prescriptions
$prescriptions = $db->query("
    SELECT p.*, pt.patient_name, d.doctor_name 
    FROM prescriptions p
    JOIN patients pt ON p.patient_id = pt.patient_id
    JOIN doctors d ON p.doctor_id = d.doctor_id
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
");
?>

<div class="main-content">
    <div class="container">
        <div class="card-header">
            <h1 class="card-title">Issue Medicine</h1>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="card">
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="issueForm">
                <div class="form-group">
                    <label class="form-label">Select Prescription *</label>
                    <select name="prescription_id" id="prescriptionSelect" class="form-control" required>
                        <option value="">Select Prescription</option>
                        <?php while($pres = $prescriptions->fetch_assoc()): ?>
                            <option value="<?php echo $pres['prescription_id']; ?>">
                                <?php echo $pres['prescription_number']; ?> - 
                                <?php echo $pres['patient_name']; ?> (Dr. <?php echo $pres['doctor_name']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div id="prescriptionDetails" style="display: none;">
                    <h3 style="margin: 2rem 0 1rem;">Prescription Items</h3>
                    <div id="itemsContainer"></div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top: 2rem;">
                        <i class="fas fa-check-circle"></i> Issue Medicine
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('prescriptionSelect').addEventListener('change', function() {
    const prescriptionId = this.value;
    const detailsDiv = document.getElementById('prescriptionDetails');
    const itemsContainer = document.getElementById('itemsContainer');
    
    if(prescriptionId) {
        // Fetch prescription details via AJAX
        fetch(`get_prescription_items.php?id=${prescriptionId}`)
            .then(response => response.json())
            .then(data => {
                let html = '<div class="table-container"><table><thead><tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Prescribed Qty</th><th>Available Stock</th><th>Issue Quantity</th></tr></thead><tbody>';
                
                data.items.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.drug_name}</td>
                            <td>${item.dosage}</td>
                            <td>${item.frequency}</td>
                            <td>${item.duration}</td>
                            <td>${item.quantity}</td>
                            <td>
                                <select name="inventory_id[]" class="form-control" required>
                                    <option value="">Select Batch</option>
                                    ${item.batches.map(batch => 
                                        `<option value="${batch.inventory_id}">Batch: ${batch.batch_number} (Qty: ${batch.quantity}, Exp: ${batch.expiry_date})</option>`
                                    ).join('')}
                                </select>
                            </td>
                            <td>
                                <input type="number" name="quantity[]" class="form-control" min="1" max="${item.quantity}" required>
                            </td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
                itemsContainer.innerHTML = html;
                detailsDiv.style.display = 'block';
            });
    } else {
        detailsDiv.style.display = 'none';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>