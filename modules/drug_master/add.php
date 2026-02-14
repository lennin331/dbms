<?php
// modules/drug_master/add.php
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $drug_name = $db->real_escape_string($_POST['drug_name']);
    $generic_name = $db->real_escape_string($_POST['generic_name']);
    $category = $db->real_escape_string($_POST['category']);
    $manufacturer = $db->real_escape_string($_POST['manufacturer']);
    $unit_price = $db->real_escape_string($_POST['unit_price']);
    
    $sql = "INSERT INTO drugs (drug_name, generic_name, category, manufacturer, unit_price) 
            VALUES ('$drug_name', '$generic_name', '$category', '$manufacturer', $unit_price)";
    
    if($db->query($sql)) {
        header("Location: list.php?msg=added");
        exit();
    } else {
        $error = "Error: " . $db->error;
    }
}
?>

<div class="main-content">
    <div class="container">
        <div class="card-header">
            <h1 class="card-title">Add New Drug</h1>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Drug Name *</label>
                    <input type="text" name="drug_name" class="form-control" required 
                           placeholder="Enter drug name">
                </div>

                <div class="form-group">
                    <label class="form-label">Generic Name</label>
                    <input type="text" name="generic_name" class="form-control" 
                           placeholder="Enter generic name">
                </div>

                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="Analgesic">Analgesic</option>
                        <option value="Antibiotic">Antibiotic</option>
                        <option value="Antacid">Antacid</option>
                        <option value="Antihistamine">Antihistamine</option>
                        <option value="Antiviral">Antiviral</option>
                        <option value="Cardiovascular">Cardiovascular</option>
                        <option value="CNS">Central Nervous System</option>
                        <option value="Dermatological">Dermatological</option>
                        <option value="Endocrine">Endocrine</option>
                        <option value="Gastrointestinal">Gastrointestinal</option>
                        <option value="Respiratory">Respiratory</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Manufacturer</label>
                    <input type="text" name="manufacturer" class="form-control" 
                           placeholder="Enter manufacturer name">
                </div>

                <div class="form-group">
                    <label class="form-label">Unit Price ($) *</label>
                    <input type="number" step="0.01" name="unit_price" class="form-control" required 
                           placeholder="0.00">
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Drug
                    </button>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>