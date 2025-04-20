<?php
session_start();
if ($_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Dashboard</title>
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
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .dashboard a:hover {
            background-color: #1e7e34;
        }
    </style>
</head>
<body>
    <?php
    $page = 'employee_dashboard';
    include 'partials/sidebar.php'; 
    ?>

    <div class="dashboard">
        <h2>Employee Dashboard</h2>
        <a href="receipts/add.php">🧾 Add Receipt</a>
        <a href="receipts/view.php">📂 View My Receipts</a>
        <a href="reports/all_receipts.php">📄 All Receipts Report</a>
        <a href="process/logout.php">🚪 Logout</a>
    </div>
</body>
</html>