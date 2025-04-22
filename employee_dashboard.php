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
    <link rel="stylesheet" href="styles/employee/employee_dashboard.css">
    <link rel="stylesheet" href="partials/sidebar.css">
</head>
<body>
    <?php
    $page = 'employee_dashboard';
    include 'partials/sidebar.php'; 
    ?>

    <div class="dashboard">
        <h1 class="dashboard-header">Employee Dashboard</h1>

        <div class="client-dropdown">
            <label for="clientFilter">Choose Client: </label>
                <select id="clientFilter">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $client) { ?>
                        <option value="<?php echo htmlspecialchars($client['id']); ?>" 
                            <?php echo ($client['id'] == $clientFilter) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name'] ?? "Unknown"); ?>
                        </option>
                    <?php } ?>
                </select>
        </div>

        <div class="container">
            <div class="section shortcut">
                <h2>Shortcuts</h2>
                <div class="shortcut-container">
                    <a href="receipts/add.php">
                        <span>🧾</span>                    
                        <label for="">Add Receipt</label>
                    </a>

                    <a href="receipts/view.php">
                        <span>📂</span>
                        <label for="">View My Receipts</label>
                    </a>

                    <a href="reports/all_receipts.php">
                        <span>📄</span> 
                        <label for="">All Receipts Report</label>
                    </a>

                    <a href="process/logout.php">
                        <span>🚪</span> 
                        <label for="">Logout</label>
                    </a>
                </div>
            </div>

            <div class="section task">
                <h2>Task</h2>
                <div class="task-container">

                </div>
            </div>
        </div>

        <div class="container">
            <div class="box-container">
                <div class="box">
                    <h1>Bank</h1>
                    <span>amount</span>
                </div>

                <div class="box">
                    <h1>Profits and loss</h1>
                    <span>amount</span>
                </div>

                <div class="box">
                    <h1>Expenses</h1>
                    <span>amount</span>
                </div>

                <div class="box">
                    <h1>Invoices</h1>
                    <span>amount</span>
                </div>
            </div>
        </div>

    </div>
</body>
</html>