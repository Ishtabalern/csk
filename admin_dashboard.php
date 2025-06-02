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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fafafa;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }
        .dashboard {
            max-width: 900px;
            width: 100%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
        }
        .dashboard h2 {
            grid-column: 1 / -1;
            text-align: center;
            color: #222;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 30px;
            user-select: none;
        }
        .dashboard a {
            background: white;
            padding: 25px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(50,50,93,0.11), 0 1px 3px rgba(0,0,0,0.08);
            text-decoration: none;
            color:rgb(102, 102, 102);
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: 
                box-shadow 0.3s ease, 
                transform 0.2s ease,
                background-color 0.3s ease, 
                color 0.3s ease;
            user-select: none;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .dashboard a:hover,
        .dashboard a:focus {
            color: #0B440F;
            transform: translateY(-6px);
            border-color:  #0B440F;
            outline: none;
        }
        .dashboard a span.icon {
            font-size: 1.6rem;
        }
    </style>
</head>
<body>
    <?php 
    $page = 'admin_dashboard';
    include 'partials/sidebar.php'; 
    ?>
    <main class="dashboard" role="main">
        <h2 style="font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;">Admin Dashboard</h2>
        <a href="admin/clients/add.php" tabindex="0"><span class="icon">➕</span> Add New Client</a>
        <a href="admin/categories.php" tabindex="0"><span class="icon">🏷️</span> Categories</a>
        <a href="admin/clients/list.php" tabindex="0"><span class="icon">📋</span> View Clients</a>
        <a href="employees/manage.php" tabindex="0"><span class="icon">👥</span> Manage Employees</a>
        <a href="admin/receipts/add.php" tabindex="0"><span class="icon">🧾</span> Add Receipt</a>
        <a href="admin/receipts/list.php" tabindex="0"><span class="icon">📂</span> View All Receipts</a>
        <a href="reports/all_receipts.php" tabindex="0"><span class="icon">📄</span> View All Receipts Report</a>
        <a href="admin/reports/sales_expense.php" tabindex="0"><span class="icon">📈</span> View Sales Vs Expense Report</a>
        <a href="admin/reports/category_summary.php" tabindex="0"><span class="icon">📊</span> View Category Summary</a>
        <a href="process/logout.php" tabindex="0"><span class="icon">🚪</span> Logout</a>
    </main>
</body>
</html>