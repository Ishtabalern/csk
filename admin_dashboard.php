<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>

<h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
<ul>
    <li><a href="admin/clients/list.php">View Clients</a></li>
    <li><a href="admin/receipts/receipts.php">View Receipts</a></li>
    <li><a href="expenses/view.php">View Expenses</a></li>
    <li><a href="sales/view.php">View Sales</a></li>
    <li><a href="reports/summary.php">Reports</a></li>
    <li><a href="employees/manage.php">Manage Employees</a></li>
    <li><a href="process/logout.php">Logout</a></li>
</ul>
