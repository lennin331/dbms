<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="/pharma/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </li>
        
        <!-- Module 1: Drug Master -->
        <li class="menu-header">DRUG MASTER</li>
        <li>
            <a href="/pharma/modules/drug_master/list.php" class="<?php echo strpos($current_page, 'drug_master') !== false ? 'active' : ''; ?>">
                <i class="fas fa-capsules"></i>
                Drug List
            </a>
        </li>
        <li>
            <a href="/pharma/modules/drug_master/add.php">
                <i class="fas fa-plus-circle"></i>
                Add New Drug
            </a>
        </li>
        <li>
            <a href="/pharma/modules/inventory/list.php">
                <i class="fas fa-boxes"></i>
                Inventory
            </a>
        </li>
        
        <!-- Module 2: Prescriptions -->
        <li class="menu-header">PRESCRIPTIONS</li>
        <li>
            <a href="/pharma/modules/prescriptions/list.php">
                <i class="fas fa-prescription"></i>
                All Prescriptions
            </a>
        </li>
        <li>
            <a href="/pharma/modules/prescriptions/add.php">
                <i class="fas fa-file-prescription"></i>
                New Prescription
            </a>
        </li>
        <li>
            <a href="/pharma/modules/doctors/list.php">
                <i class="fas fa-user-md"></i>
                Doctors
            </a>
        </li>
        <li>
            <a href="/pharma/modules/patients/list.php">
                <i class="fas fa-users"></i>
                Patients
            </a>
        </li>
        
        <!-- Module 3: Transactions -->
        <li class="menu-header">TRANSACTIONS</li>
        <li>
            <a href="/pharma/modules/transactions/issue.php">
                <i class="fas fa-arrow-right"></i>
                Issue Medicine
            </a>
        </li>
        <li>
            <a href="/pharma/modules/transactions/return.php">
                <i class="fas fa-arrow-left"></i>
                Return Medicine
            </a>
        </li>
        <li>
            <a href="/pharma/modules/transactions/list.php">
                <i class="fas fa-history"></i>
                Transaction History
            </a>
        </li>
        
        <!-- Module 4: Reports -->
        <li class="menu-header">REPORTS</li>
        <li>
            <a href="/pharma/modules/reports/expiry_alert.php">
                <i class="fas fa-exclamation-triangle"></i>
                Expiry Alerts
            </a>
        </li>
        <li>
            <a href="/pharma/modules/reports/stock_report.php">
                <i class="fas fa-chart-bar"></i>
                Stock Report
            </a>
        </li>
        <li>
            <a href="/pharma/modules/reports/sales_report.php">
                <i class="fas fa-chart-line"></i>
                Sales Report
            </a>
        </li>
    </ul>
</nav>