<?php
// modules/inventory/add.php
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $drug_id = $db->real_escape_string($_POST['drug_id']);
    $batch_number = $db->real_escape_string($_POST['batch_number']);
    $quantity = $db->real_escape_string($_POST['quantity']);
    $expiry_date = $db->real_escape_string($_POST['expiry_date']);
    $mfg_date = $db->real_escape_string($_POST['mfg_date']) ?: null;
    $location = $db->real_escape_string($_POST['location']);
    
    $sql = "INSERT INTO inventory (drug_id, batch_number, quantity, expiry_date, mfg_date, location) 
            VALUES ($drug_id, '$batch_number', $quantity, '$expiry_date', " . 
            ($mfg_date ? "'$mfg_date'" : "NULL") . ", '$location')";
    
    if($db->query($sql)) {
        header("Location: list.php?msg=added");
        exit();
    } else {
        $error = "Error: " . $db->error;
    }
}

// Get drugs for dropdown
$drugs = $db->query("SELECT drug_id, drug_name FROM drugs ORDER BY drug_name");
?>

<div class="main-content">
    <div class="container">
        <div class="card-header">
            <h1 class="card-title">Add Stock to Inventory</h1>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Inventory
            </a>
        </div>

        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Select Drug *</label>
                    <select name="drug_id" class="form-control" required>
                        <option value="">Select Drug</option>
                        <?php while($drug = $drugs->fetch_assoc()): ?>
                            <option value="<?php echo $drug['drug_id']; ?>">
                                <?php echo $drug['drug_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Batch Number *</label>
                    <input type="text" name="batch_number" class="form-control" required 
                           placeholder="Enter batch number">
                </div>

                <div class="form-group">
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="quantity" class="form-control" required 
                           min="0" placeholder="Enter quantity">
                </div>

                <div class="form-group">
                    <label class="form-label">Expiry Date *</label>
                    <input type="date" name="expiry_date" class="form-control" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Manufacturing Date</label>
                    <input type="date" name="mfg_date" class="form-control" 
                           max="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Storage Location</label>
                    <input type="text" name="location" class="form-control" 
                           placeholder="e.g., Shelf A-01, Rack B-02">
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add to Inventory
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