<?php
session_start();
include '../includes/db.php';

$clients = $conn->query("SELECT id, name FROM clients");

// Filters
$client_id = $_GET['client_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$income = [];
$expenses = [];
$total_income = 0;
$total_expenses = 0;

if ($client_id) {
    $stmt = $conn->prepare("
        SELECT c.name AS category_name, c.type, SUM(r.amount) AS total_amount
        FROM receipts r
        JOIN categories c ON r.category = c.name AND c.client_id = r.client_id
        WHERE r.client_id = ? 
          AND r.receipt_date BETWEEN ? AND ?
          AND c.type IN ('income', 'expense')
        GROUP BY c.name, c.type
        ORDER BY FIELD(c.type, 'income', 'expense')
    ");
    $stmt->bind_param("iss", $client_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $income = [];
    $expenses = [];
    $total_income = 0;
    $total_expenses = 0;

    while ($row = $result->fetch_assoc()) {
        $amount = $row['total_amount'];
        if ($row['type'] === 'income') {
            $income[] = ['name' => $row['category_name'], 'amount' => $amount];
            $total_income += $amount;
        } else {
            $expenses[] = ['name' => $row['category_name'], 'amount' => $amount];
            $total_expenses += $amount;
        }
    }
}



$client_name = '';
if (!empty($client_id)) {
    $result = $conn->query("SELECT name FROM clients WHERE id = " . (int)$client_id);
    if ($row = $result->fetch_assoc()) {
        $client_name = $row['name'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Income Statement</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../partials/topbar.css">
    <link rel="stylesheet" href="../styles/reports/income_statement.css">
</head>
<body>

    <div class="topbar-container">
        <div class="header">
            <img src="../imgs/csk_logo.png" alt="">
            <h1>Income Statement</h1>
        </div>
       
       
        <div class="btn">
            <?php
                $dashboard_link = ($_SESSION['role'] === 'admin') ? 'view_reports.php' : 'view_reports.php';
            ?>
            <a href="<?= $dashboard_link ?>">
                Reports
            </a>
            <?php
                $dashboard_link = ($_SESSION['role'] === 'admin') ? '../admin_dashboard.php' : '../employee_dashboard.php';
            ?>
            <a href="<?= $dashboard_link ?>">
                Dashboard
            </a>
        </div>
    </div>
  


    <div class="client-container">
        <form class="client" method="get">
            <div class="section">
                
                <div class="input">
                    <label>Client:</label>
                    <select name="client_id" required>
                        <option value="">Select Client</option>
                        <?php while ($c = $clients->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id'] == $client_id ? 'selected' : '' ?>><?= $c['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input">             
                    <label>From:</label>
                    <input type="date" name="start_date" value="<?= $start_date ?>" required>
                </div>

                <div class="input">              
                    <label>To:</label>
                    <input type="date" name="end_date" value="<?= $end_date ?>" required>
                </div>

            </div>
        
            <button type="submit">Generate</button>
        </form>
    </div>
 
    <?php if ($client_id): ?>

        <div class="exports-btn">
            <form id="exportPDFForm" method="POST" action="../process/income_statement_export.php" target="_blank">
            <input type="hidden" name="html_content" id="html_content">
            <button type="submit" name="export_pdf">Export as PDF</button>
            </form>
                <script>
                document.getElementById('exportPDFForm').addEventListener('submit', function (e) {
                const tableHtml = document.getElementById('income_statement_table').outerHTML;
                document.getElementById('html_content').value = tableHtml;
                });
                </script>

            <form id="exportExcelForm" method="POST" action="../process/balance_export_excel.php">
            <input type="hidden" name="client_id" value="<?= $client_id ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <button type="submit">Export to Excel</button>
            </form>
                <script>
                document.getElementById('exportExcelForm').addEventListener('submit', function (e) {
                const tableHtml = document.getElementById('income_statement_table').outerHTML;
                document.getElementById('excel_html_content').value = tableHtml;
                });
                </script>
        </div>

        <div class="incomeStatement-container" id="income_statement_table">            
            <div class="customer-name">              
                <p><?= htmlspecialchars($client_name) ?></p>
                <h3 id="tab-content">Income Statement</h3>
                <h3>From <?= date("m-d-Y", strtotime($start_date)) ?> to <?= date("m-d-Y", strtotime($end_date)) ?></h3>
            </div>

            <div class="table">
                <table border="1" cellpadding="5">
                    <tr><td class="left bold">Income</td></tr>
                    <tr><th>Account</th><th>Amount</th></tr>
                    <?php foreach ($income as $i): ?>
                        <tr><td><?= $i['name'] ?></td><td><?= number_format($i['amount'], 2) ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td><strong>Total Income</strong></td><td><strong><?= number_format($total_income, 2) ?></strong></td></tr>
                </table>
            </div>

            <div class="table">
                <table border="1" cellpadding="5">
                    <tr><td class="left bold">Expense</td></tr>
                    <tr><th>Account</th><th>Amount</th></tr>
                    <?php foreach ($expenses as $e): ?>
                        <tr><td><?= $e['name'] ?></td><td><?= number_format($e['amount'], 2) ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td><strong>Total Expenses</strong></td><td><strong><?= number_format($total_expenses, 2) ?></strong></td></tr>
                </table>
            </div>

            <h3 style="color:rgb(141, 38, 0); padding:0px 35px;">
            <?= ($total_income - $total_expenses) >= 0 ? 'Net Profit' : 'Net Loss' ?>:
            <?= number_format($total_income - $total_expenses, 2) ?>
            </h3>
        </div>
        
    
    <?php endif; ?>
    
        
      
</body>
</html>
