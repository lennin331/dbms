<?php
// index.php - Landing Page
require_once 'config/database.php';
?>
<?php include 'includes/header.php'; ?>

<div class="main-content" style="margin-left: 0; text-align: center; padding: 4rem;">
    <div class="container">
        <div class="card" style="max-width: 600px; margin: 2rem auto;">
            <div style="font-size: 4rem; color: var(--accent-primary); margin-bottom: 1rem;">
                <i class="fas fa-pills"></i>
            </div>
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Pharma<span style="color: var(--accent-primary);">Manage</span></h1>
            <p style="color: var(--text-secondary); margin-bottom: 2rem; font-size: 1.1rem;">
                A Secure Web-Based Pharmaceutical Drug Stock and Prescription Management System
            </p>
            
            <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 2rem;">
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
                        <i class="fas fa-prescription"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Prescriptions</h3>
                        <?php
                        $result = $db->query("SELECT COUNT(*) as count FROM prescriptions");
                        $count = $result->fetch_assoc()['count'];
                        ?>
                        <div class="stat-value"><?php echo $count; ?></div>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i>
                    Go to Dashboard
                </a>
                <a href="modules/drug_master/list.php" class="btn btn-secondary">
                    <i class="fas fa-capsules"></i>
                    View Drugs
                </a>
            </div>
        </div>
        
        <!-- Project Info Cards -->
        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-top: 3rem;">
            <div class="card" style="text-align: left;">
                <i class="fas fa-database" style="color: var(--accent-primary); font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3>Module 1</h3>
                <p style="color: var(--text-secondary);">Drug Master & Inventory Structuring</p>
            </div>
            
            <div class="card" style="text-align: left;">
                <i class="fas fa-file-prescription" style="color: var(--success); font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3>Module 2</h3>
                <p style="color: var(--text-secondary);">Prescription Entry & Validation</p>
            </div>
            
            <div class="card" style="text-align: left;">
                <i class="fas fa-exchange-alt" style="color: var(--warning); font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3>Module 3</h3>
                <p style="color: var(--text-secondary);">Stock Consistency & Transaction Control</p>
            </div>
            
            <div class="card" style="text-align: left;">
                <i class="fas fa-chart-pie" style="color: var(--danger); font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3>Module 4</h3>
                <p style="color: var(--text-secondary);">Compliance Reports & Query Optimization</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>