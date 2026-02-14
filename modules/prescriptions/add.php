<?php
// modules/prescriptions/add.php
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Generate unique prescription number
function generatePrescriptionNumber($db) {
    $prefix = 'RX';
    $year = date('Y');
    $month = date('m');
    
    $result = $db->query("
        SELECT COUNT(*) as count 
        FROM prescriptions 
        WHERE prescription_number LIKE '$prefix$year$month%'
    ");
    $count = $result->fetch_assoc()['count'] + 1;
    
    return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $prescription_number = generatePrescriptionNumber($db);
    $doctor_id = $db->real_escape_string($_POST['doctor_id']);
    $patient_id = $db->real_escape_string($_POST['patient_id']);
    $prescription_date = $db->real_escape_string($_POST['prescription_date']);
    $diagnosis = $db->real_escape_string($_POST['diagnosis']);
    
    // Start transaction
    $db->begin_transaction();
    
    try {
        // Insert prescription
        $sql = "INSERT INTO prescriptions (prescription_number, doctor_id, patient_id, prescription_date, diagnosis) 
                VALUES ('$prescription_number', $doctor_id, $patient_id, '$prescription_date', '$diagnosis')";
        
        if(!$db->query($sql)) {
            throw new Exception("Error inserting prescription: " . $db->error);
        }
        
        $prescription_id = $db->insert_id;
        
        // Insert prescription details
        $drugs = $_POST['drug_id'];
        $dosages = $_POST['dosage'];
        $frequencies = $_POST['frequency'];
        $durations = $_POST['duration'];
        $quantities = $_POST['quantity'];
        $instructions = $_POST['instructions'];
        
        for($i = 0; $i < count($drugs); $i++) {
            if(!empty($drugs[$i])) {
                $drug_id = $db->real_escape_string($drugs[$i]);
                $dosage = $db->real_escape_string($dosages[$i]);
                $frequency = $db->real_escape_string($frequencies[$i]);
                $duration = $db->real_escape_string($durations[$i]);
                $quantity = $db->real_escape_string($quantities[$i]);
                $instruction = $db->real_escape_string($instructions[$i]);
                
                $detail_sql = "INSERT INTO prescription_details 
                              (prescription_id, drug_id, dosage, frequency, duration, quantity, instructions) 
                              VALUES ($prescription_id, $drug_id, '$dosage', '$frequency', '$duration', $quantity, '$instruction')";
                
                if(!$db->query($detail_sql)) {
                    throw new Exception("Error inserting prescription details: " . $db->error);
                }
            }
        }
        
        $db->commit();
        header("Location: list.php?msg=added");
        exit();
        
    } catch(Exception $e) {
        $db->rollback();
        $error = $e->getMessage();
    }
}

// Get doctors, patients, and drugs for dropdowns
$doctors = $db->query("SELECT doctor_id, doctor_name FROM doctors ORDER BY doctor_name");
$patients = $db->query("SELECT patient_id, patient_name FROM patients ORDER BY patient_name");
$drugs = $db->query("SELECT drug_id, drug_name FROM drugs ORDER BY drug_name");
?>

<div class="main-content">
    <div class="container">
        <div class="card-header">
            <h1 class="card-title">New Prescription</h1>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="card">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="prescriptionForm">
                <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="form-group">
                        <label class="form-label">Doctor *</label>
                        <select name="doctor_id" class="form-control" required>
                            <option value="">Select Doctor</option>
                            <?php while($doctor = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doctor['doctor_id']; ?>">
                                    <?php echo $doctor['doctor_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Patient *</label>
                        <select name="patient_id" class="form-control" required>
                            <option value="">Select Patient</option>
                            <?php while($patient = $patients->fetch_assoc()): ?>
                                <option value="<?php echo $patient['patient_id']; ?>">
                                    <?php echo $patient['patient_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Prescription Date *</label>
                    <input type="date" name="prescription_date" class="form-control" required 
                           value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Diagnosis</label>
                    <textarea name="diagnosis" class="form-control" rows="3" 
                              placeholder="Enter diagnosis or medical notes"></textarea>
                </div>

                <h3 style="margin: 2rem 0 1rem;">Prescription Items</h3>
                
                <div id="prescriptionItems">
                    <!-- Prescription items will be added here dynamically -->
                </div>

                <button type="button" class="btn btn-secondary" onclick="addPrescriptionItem()" style="margin-bottom: 2rem;">
                    <i class="fas fa-plus"></i> Add Medicine
                </button>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Prescription
                    </button>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let itemCount = 0;
const drugs = <?php 
    $drugs_array = [];
    $drugs->data_seek(0);
    while($drug = $drugs->fetch_assoc()) {
        $drugs_array[] = $drug;
    }
    echo json_encode($drugs_array);
?>;

function addPrescriptionItem() {
    const container = document.getElementById('prescriptionItems');
    const item = document.createElement('div');
    item.className = 'prescription-item';
    item.style = 'border: 1px solid var(--border-color); padding: 1rem; margin-bottom: 1rem; border-radius: 8px;';
    
    let drugOptions = '<option value="">Select Medicine</option>';
    drugs.forEach(drug => {
        drugOptions += `<option value="${drug.drug_id}">${drug.drug_name}</option>`;
    });
    
    item.innerHTML = `
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <h4>Medicine #${itemCount + 1}</h4>
            <button type="button" class="btn btn-danger" style="padding: 0.25rem 0.75rem;" onclick="this.closest('.prescription-item').remove()">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
            <div class="form-group">
                <label class="form-label">Medicine *</label>
                <select name="drug_id[]" class="form-control" required>
                    ${drugOptions}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Dosage *</label>
                <input type="text" name="dosage[]" class="form-control" required placeholder="e.g., 500mg">
            </div>
            <div class="form-group">
                <label class="form-label">Frequency *</label>
                <input type="text" name="frequency[]" class="form-control" required placeholder="e.g., Twice daily">
            </div>
            <div class="form-group">
                <label class="form-label">Duration *</label>
                <input type="text" name="duration[]" class="form-control" required placeholder="e.g., 7 days">
            </div>
            <div class="form-group">
                <label class="form-label">Quantity *</label>
                <input type="number" name="quantity[]" class="form-control" required min="1">
            </div>
            <div class="form-group">
                <label class="form-label">Instructions</label>
                <input type="text" name="instructions[]" class="form-control" placeholder="e.g., After meals">
            </div>
        </div>
    `;
    
    container.appendChild(item);
    itemCount++;
}

// Add first item by default
addPrescriptionItem();
</script>

<?php include '../../includes/footer.php'; ?>