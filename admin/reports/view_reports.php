<?php
session_start();
require_once '../../includes/db.php';

// Optional: Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="../../partials/sidebar.css">
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
            background-color: #062335;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .dashboard a:hover {
            background-color:rgb(3, 20, 31);
        }
    </style>
</head>
<body>
    <?php
        $page = 'view_reports_admin';
        include '../../partials/sidebar.php'; 
        ?>
<div class="dashboard">
        <h2>Reports</h2>
        <a href="../../reports/balance_sheet.php">Balance Sheet</a>
        <a href="category_summary.php">Category Summary</a>
        <a href="../../reports/client_summary.php">Client Summary</a>
        <a href="../../reports/payment_methods.php">Payment Methods</a>
        <a href="sales_expense.php">Sales Vs Expense</a>
        <a href="../../reports/trial_balance.php">Trial Balance</a>
    </div>
</body>
</html>