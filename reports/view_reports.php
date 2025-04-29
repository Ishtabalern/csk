<?php
session_start();
require_once '../includes/db.php';

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
    <link rel="stylesheet" href="../partials/sidebar.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            width: 100%;
            height: 100%;
            --color: #E1E1E1;
            background-color: #F3F3F3;
            background-image: linear-gradient(0deg, transparent 24%, var(--color) 25%, var(--color) 26%, transparent 27%,transparent 74%, var(--color) 75%, var(--color) 76%, transparent 77%,transparent),
                linear-gradient(90deg, transparent 24%, var(--color) 25%, var(--color) 26%, transparent 27%,transparent 74%, var(--color) 75%, var(--color) 76%, transparent 77%,transparent);
            background-size: 55px 55px;
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
            background-color: rgb(219, 219, 219);
            color: #0B440F;
        }
    </style>
</head>
<body>
    <?php
        $page = 'view_reports';
        include '../partials/sidebar.php'; 
        ?>
<div class="dashboard">
        <h2>Reports</h2>
        <a href="balance_sheet.php">Balance Sheet</a>
        <a href="category_summary.php">Category Summary</a>
        <a href="client_summary.php">Client Summary</a>
        <a href="payment_methods.php">Payment Methods</a>
        <a href="sales_expense.php">Sales Vs Expense</a>
        <a href="trial_balance.php">Trial Balance</a>
        <a href="income_statement.php">Income Statement</a>
        <a href="owners_equity.php">Owner's Equity</a>
    </div>
</body>
</html>