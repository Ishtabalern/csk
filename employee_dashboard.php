<?php
session_start();
if ($_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit;
}
?>

<h1>Welcome, <?php echo $_SESSION['username']; ?> (Employee)</h1>
<ul>
    <li><a href="receipts/add.php">Add Receipt</a></li>
    <li><a href="receipts/view.php">My Receipts</a></li>
    <li><a href="expenses/add.php">Add Expense</a></li>
    <li><a href="sales/add.php">Add Sale</a></li>
    <li><a href="process/logout.php">Logout</a></li>
</ul>
