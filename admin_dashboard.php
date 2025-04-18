<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>

<h1>Welcome, Admin <?php echo $_SESSION['username']; ?>!</h1>
<ul>
    <li><a href="receipts/view.php">View Receipts</a></li>
    <li><a href="expenses/view.php">View Expenses</a></li>
    <li><a href="sales/view.php">View Sales</a></li>
    <li><a href="reports/summary.php">Reports</a></li>
    <li><a href="employees/manage.php">Manage Employees</a></li>
    <li><a href="process/logout.php">Logout</a></li>
</ul>
