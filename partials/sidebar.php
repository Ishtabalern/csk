<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<?php
if (!isset($page)) {
  $page = ''; // or a default value like 'home'
}
?>
<div class="sidebar">
        <div class="company-logo">
        <?php if ($page === 'sidebar'): ?>
            <img src="../imgs/csk_logo.png" alt="">
        <?php endif; ?>
        <?php if ($page === 'all_receipts'): ?>
            <img src="../imgs/csk_logo.png" alt="">
        <?php endif; ?>
        <?php if ($page === 'view_reports'): ?>
            <img src="../imgs/csk_logo.png" alt="">
        <?php endif; ?>
            <img src="imgs/csk_logo.png" alt="">
        </div>
        
        <?php if ($_SESSION['role'] === 'admin' && $page === 'admin_dashboard'): ?>
            <div class="btn-container">
                <a class="btn-tabs" href="admin/clients/add.php">Add New Client</a>
                <a class="btn-tabs" href="admin/clients/list.php">View Clients</a>
                <a class="btn-tabs" href="employees/manage.php">Manage Employees</a>
                <a class="btn-tabs" href="admin/receipts/add.php">Add Receipt</a>
                <a class="btn-tabs" href="admin/receipts/list.php">View All Receipts</a>
            </div>
            <div class="bottom-link">
                <a class="bottom-btn" href="process/logout.php">Logout</a>
            </div>    
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'admin' && $page === 'all_receipts'): ?>
            <div class="btn-container">
                <a class="btn-tabs" href="../admin/clients/add.php">Add New Client</a>
                <a class="btn-tabs" href="../admin/clients/list.php">View Clients</a>
                <a class="btn-tabs" href="../employees/manage.php">Manage Employees</a>
                <a class="btn-tabs" href="../admin/receipts/add.php">Add Receipt</a>
                <a class="btn-tabs" href="../admin/receipts/list.php">View All Receipts</a>
            </div>
            <div class="bottom-link">
                <a class="bottom-btn" href="../process/logout.php">Logout</a>
            </div> 
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] === 'employee' && $page === 'employee_dashboard'): ?>
            <div class="btn-container">
                <a class="btn-tabs" href="receipts/add.php">Add Receipt</a>
                <a class="btn-tabs" href="receipts/view.php">View My Receipts</a>
                <a class="btn-tabs" href="reports/all_receipts.php">All Receipts Report</a>
                <a class="btn-tabs" href="reports/view_reports.php">Reports</a>
            </div>
            <div class="bottom-link">
                <a class="bottom-btn" href="process/logout.php">Logout</a>
            </div> 
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] === 'employee' && $page === 'all_receipts'): ?>
            <div class="btn-container">
                <a class="btn-tabs" href="../employee_dashboard.php">Home</a>
                <a class="btn-tabs" href="../receipts/add.php">Add Receipt</a>
                <a class="btn-tabs" href="../receipts/view.php">View My Receipts</a>
                <a class="btn-tabs" href="all_receipts.php">All Receipts Report</a>
                <a class="btn-tabs" href="view_reports.php">Reports</a>
            </div>
            <div class="bottom-link">
                <a class="bottom-btn" href="process/logout.php">Logout</a>
            </div> 
        <?php endif; ?>   

        <?php if ($_SESSION['role'] === 'employee' && $page === 'view_reports'): ?>
            <div class="btn-container">
                <a class="btn-tabs" href="../employee_dashboard.php">Home</a>
                <a class="btn-tabs" href="../receipts/add.php">Add Receipt</a>
                <a class="btn-tabs" href="../receipts/view.php">View My Receipts</a>
                <a class="btn-tabs" href="all_receipts.php">All Receipts Report</a>
                <a class="btn-tabs" href="view_reports.php">Reports</a>
            </div>
            <div class="bottom-link">
                <a class="bottom-btn" href="process/logout.php">Logout</a>
            </div> 
        <?php endif; ?>
</div>
</body>
</html>