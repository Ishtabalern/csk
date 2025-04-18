<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="partials/sidebar.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .dashboard {
            width: 400px;
            margin: 100px auto;
            text-align: center;
        }
        .dashboard h2 {
            margin-bottom: 30px;
        }
        .dashboard a {
            display: block;
            margin: 10px 0;
            padding: 10px;
            background-color: #0056b3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .dashboard a:hover {
            background-color: #003f7f;
        }
    </style>
</head>
<body>
    <?php 
    $page = 'admin_dashboard';
    include 'partials/sidebar.php'; 
    ?>
    <div class="dashboard">
        <h2>Admin Dashboard</h2>
        <a href="admin/clients/add.php">➕ Add New Client</a>
        <a href="admin/clients/list.php">📋 View Clients</a>
        <a href="employees/manage.php">👥 Manage Employees</a>
        <a href="admin/receipts/add.php">🧾 Add Receipt</a>
        <a href="admin/receipts/list.php">📂 View All Receipts</a>
        <a href="reports/all_receipts.php">📄 View All Receipts Report</a>
        <a href="admin/reports/sales_expense.php">📄 View Sales Vs Expense Report</a>
        <a href="admin/reports/category_summary.php">📄 View Category Summary</a>
        <a href="process/logout.php">🚪 Logout</a>
    </div>
</body>
</html>