<?php
session_start();
include '../includes/db.php';

$clients = $conn->query("SELECT id, name FROM clients");

$client_id = $_GET['client_id'] ?? null;
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Prepare data
$accounts_data = [];
$totals = [
    'assets' => 0, 'liabilities' => 0, 'equity' => 0
];

if ($client_id) {
    $stmt = $conn->prepare("
        SELECT 
            a.id AS account_id,
            a.name AS account_name,
            a.type AS account_type,
            a.subtype,
            SUM(jl.debit) AS total_debit,
            SUM(jl.credit) AS total_credit
        FROM journal_entries je
        JOIN journal_lines jl ON je.id = jl.entry_id
        JOIN accounts a ON jl.account_id = a.id
        WHERE je.client_id = ? AND je.entry_date <= ?
        GROUP BY a.id, a.name, a.type, a.subtype
    ");
    $stmt->bind_param("is", $client_id, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $account_type = strtolower($row['account_type']); // asset, liability, equity
        $balance = 0;

        // Determine balance logic
        if ($account_type === 'asset') {
            $balance = $row['total_debit'] - $row['total_credit'];
        } elseif ($account_type === 'liability' || $account_type === 'equity') {
            $balance = $row['total_credit'] - $row['total_debit'];
        }

        $type_map = [
            'asset' => 'assets',
            'liability' => 'liabilities',
            'equity' => 'equity'
        ];
        
        if (isset($type_map[$account_type])) {
            $totals_key = $type_map[$account_type];
            $accounts_data[$totals_key][] = [
                'name' => $row['account_name'],
                'subtype' => $row['subtype'],
                'balance' => $balance
            ];
            $totals[$totals_key] += $balance;
        }        
    }
}

// Step 1: Get beginning capital
$beginning_capital = 0;
$cap_stmt = $conn->prepare("SELECT amount FROM beginning_capital WHERE client_id = ? ORDER BY effective_date DESC LIMIT 1");
$cap_stmt->bind_param("i", $client_id);
$cap_stmt->execute();
$cap_stmt->bind_result($beginning_capital);
$cap_stmt->fetch();
$cap_stmt->close();

// Step 2: Compute Net Income
$income_stmt = $conn->prepare("
    SELECT a.type, SUM(jl.debit) AS debit, SUM(jl.credit) AS credit
    FROM journal_entries je
    JOIN journal_lines jl ON je.id = jl.entry_id
    JOIN accounts a ON jl.account_id = a.id
    WHERE je.client_id = ? AND je.entry_date <= ?
    AND a.type IN ('Revenue', 'Expense')
    GROUP BY a.type
");
$income_stmt->bind_param("is", $client_id, $end_date);
$income_stmt->execute();
$income_result = $income_stmt->get_result();

$net_income = 0;
while ($row = $income_result->fetch_assoc()) {
    if ($row['type'] === 'Revenue') {
        $net_income += $row['credit'] - $row['debit'];
    } elseif ($row['type'] === 'Expense') {
        $net_income -= $row['debit'] - $row['credit'];
    }
}
$income_stmt->close();

// Step 3: Get total Owner’s Withdrawals
$withdrawals = 0;
$withdraw_stmt = $conn->prepare("
    SELECT SUM(jl.debit - jl.credit) AS withdrawal_total
    FROM journal_entries je
    JOIN journal_lines jl ON je.id = jl.entry_id
    JOIN accounts a ON jl.account_id = a.id
    WHERE je.client_id = ? AND je.entry_date <= ? AND a.name = 'Owner’s Withdrawal'
");
$withdraw_stmt->bind_param("is", $client_id, $end_date);
$withdraw_stmt->execute();
$withdraw_stmt->bind_result($withdrawals);
$withdraw_stmt->fetch();
$withdraw_stmt->close();

// Final Equity Calculation
$total_equity = $beginning_capital + $net_income - $withdrawals;
$totals['equity'] = $total_equity;

// Fetch client name
$client_name = '';
if ($client_id) {
    $client_stmt = $conn->prepare("SELECT name FROM clients WHERE id = ?");
    $client_stmt->bind_param("i", $client_id);
    $client_stmt->execute();
    $client_stmt->bind_result($client_name);
    $client_stmt->fetch();
    $client_stmt->close();
}


?>


<!DOCTYPE html>
<html>
<head>
    <title>Balance Sheet</title>
    <link rel="stylesheet" href="../styles/reports/balance_sheet.css">
    <link rel="stylesheet" href="../partials/topbar.css">
    <style>
        
       
    </style>
</head>
<body>

<div class="topbar-container">
    <div class="header">
        <img src="../imgs/csk_logo.png" alt="">
        <h1>Balance Sheet</h1>
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

<div class="container">
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
                        <label>Date:</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>">
                    </div>
            </div>
            
                <button type="submit">Generate</button>
        </form>
    </div>

    <?php if ($client_id): ?>
        <div class="exports-btn">
            <form id="exportPDFForm" method="POST" action="../process/balance_export.php" target="_blank">
            <input type="hidden" name="html_content" id="html_content">
            <button type="submit" name="export_pdf">Export as PDF</button>
            </form>
                <script>
                document.getElementById('exportPDFForm').addEventListener('submit', function (e) {
                const tableHtml = document.getElementById('balance_sheet_table').outerHTML;
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
                const tableHtml = document.getElementById('balance_sheet_table').outerHTML;
                document.getElementById('excel_html_content').value = tableHtml;
                });
                </script>
        </div>

        <div id ="balance_sheet_table">
            <h2 style="margin-top:15px; color:#1ABC9C;">Balance Sheet of <?= htmlspecialchars($client_name) ?> as of <?= htmlspecialchars(date("m-d-Y", strtotime($end_date))) ?></h2>

            <div class="table-container">
                <h4>Assets</h4>
                <table class="">
                    <tr><th>Account</th><th>Amount</th></tr>
                    <?php foreach ($accounts_data['assets'] ?? [] as $acc): ?>
                        <tr>
                            <td><?= htmlspecialchars($acc['name']) ?></td>
                            <td><?= number_format($acc['balance'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total"><td>Total Assets</td><td><?= number_format($totals['assets'], 2) ?></td></tr>
                </table>
            </div>

            <div class="table-container">
                <h4>Liabilities</h4>
                <table>
                    <tr><th>Account</th><th>Amount</th></tr>
                    <?php foreach ($accounts_data['liabilities'] ?? [] as $acc): ?>
                        <tr>
                            <td><?= htmlspecialchars($acc['name']) ?></td>
                            <td><?= number_format($acc['balance'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total"><td>Total Liabilities</td><td><?= number_format($totals['liabilities'], 2) ?></td></tr>
                </table>
            </div>

            <div class="table-container">
                <h4>Equity</h4>
                <table>
                    <tr><th>Component</th><th>Amount</th></tr>
                    <tr><td>Beginning Capital</td><td><?= number_format($beginning_capital, 2) ?></td></tr>
                    <tr><td>Net Income</td><td><?= number_format($net_income, 2) ?></td></tr>
                    <tr><td>Withdrawals</td><td>(<?= number_format($withdrawals, 2) ?>)</td></tr>
                    <tr class="total"><td>Total Equity</td><td><?= number_format($total_equity, 2) ?></td></tr>
                </table>
            </div>

            <div class="table-container">
                <h4>Total Liabilities & Equity</h4>
                <table>
                    <tr><td class="total">Total</td><td class="total"><?= number_format($totals['liabilities'] + $total_equity, 2) ?></td></tr>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>