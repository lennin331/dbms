<?php
// modules/drug_master/edit.php
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

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $drug_name = $db->real_escape_string($_POST['drug_name']);
    $generic_name = $db->real_escape_string($_POST['generic_name']);
    $category = $db->real_escape_string($_POST['category']);
    $manufacturer = $db->real_escape_string($_POST['manufacturer']);
    $unit_price = $db->real_escape_string($_POST['unit_price']);
    
    $sql = "UPDATE drugs SET 
            drug_name = '$drug_name',
            generic_name = '$generic_name',
            category = '$category',
            manufacturer = '$manufacturer',
            unit_price = $unit_price
            WHERE drug_id = $id";
    
    if($db->query($sql)) {
        header("Location: list.php?msg=updated");
        exit();
    } else {
        $error = "Error: " . $db->error;
    }
}
?>

<div class="main-content">
    <div class="container">
        <div class="card-header">
            <h1 class="card-title">Edit Drug: <?php echo $drug['drug_name']; ?></h1>
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
                           value="<?php echo $drug['drug_name']; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Generic Name</label>
                    <input type="text" name="generic_name" class="form-control" 
                           value="<?php echo $drug['generic_name']; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="Analgesic" <?php echo $drug['category'] == 'Analgesic' ? 'selected' : ''; ?>>Analgesic</option>
                        <option value="Antibiotic" <?php echo $drug['category'] == 'Antibiotic' ? 'selected' : ''; ?>>Antibiotic</option>
                        <option value="Antacid" <?php echo $drug['category'] == 'Antacid' ? 'selected' : ''; ?>>Antacid</option>
                        <option value="Other" <?php echo $drug['category'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Manufacturer</label>
                    <input type="text" name="manufacturer" class="form-control" 
                           value="<?php echo $drug['manufacturer']; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Unit Price ($) *</label>
                    <input type="number" step="0.01" name="unit_price" class="form-control" required 
                           value="<?php echo $drug['unit_price']; ?>">
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Drug
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