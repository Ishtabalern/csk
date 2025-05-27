<?php
    session_start();
    include '../includes/db.php';

    $clients = $conn->query("SELECT id, name FROM clients");
    $client_id = $_GET['client_id'] ?? null;
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    $operating_inflows = 0;
    $operating_outflows = 0;
    $financing_inflows = 0;
    $financing_outflows = 0;
    $ending_cash = 0;
    $beginning_cash = 0;

    if ($client_id) {
        // Get Cash Balance at the Beginning of the Year
        $beginning_cash = 0;
        $start_of_year = date('Y-01-01', strtotime($end_date));

        $stmt = $conn->prepare("
            SELECT SUM(jl.debit - jl.credit) AS cash_balance
            FROM journal_entries je
            JOIN journal_lines jl ON je.id = jl.entry_id
            JOIN accounts a ON jl.account_id = a.id
            WHERE je.client_id = ? AND je.entry_date < ? AND a.name = 'Cash'
        ");
        $stmt->bind_param("is", $client_id, $start_of_year);
        $stmt->execute();
        $stmt->bind_result($beginning_cash);
        $stmt->fetch();
        $stmt->close();
        
        // Add beginning capital if not posted in journal entries
        $stmt = $conn->prepare("
            SELECT amount FROM beginning_capital
            WHERE client_id = ? AND effective_date <= ?
            ORDER BY effective_date DESC LIMIT 1
        ");
        $stmt->bind_param("is", $client_id, $start_of_year);
        $stmt->execute();
        $stmt->bind_result($beginning_capital);
        $stmt->fetch();
        $stmt->close();

        $beginning_capital = $beginning_capital ?? 0;
        $beginning_cash += $beginning_capital;


        // Operating Activities: Sales (Revenue)
        $stmt = $conn->prepare("
            SELECT SUM(jl.credit - jl.debit) AS total
            FROM journal_entries je
            JOIN journal_lines jl ON je.id = jl.entry_id
            JOIN accounts a ON jl.account_id = a.id
            WHERE je.client_id = ? AND je.entry_date <= ? AND a.type = 'Revenue'
        ");
        $stmt->bind_param("is", $client_id, $end_date);
        $stmt->execute();
        $stmt->bind_result($operating_inflows);
        $stmt->fetch();
        $stmt->close();

        // Operating Activities: Expenses
        $stmt = $conn->prepare("
            SELECT SUM(jl.debit - jl.credit) AS total
            FROM journal_entries je
            JOIN journal_lines jl ON je.id = jl.entry_id
            JOIN accounts a ON jl.account_id = a.id
            WHERE je.client_id = ? AND je.entry_date <= ?
            AND a.type = 'Expense'
            AND je.id NOT IN (
                SELECT je2.id
                FROM journal_entries je2
                JOIN journal_lines jl2 ON je2.id = jl2.entry_id
                JOIN accounts a2 ON jl2.account_id = a2.id
                WHERE a2.type = 'Liability'
            )

        ");
        $stmt->bind_param("is", $client_id, $end_date);
        $stmt->execute();
        $stmt->bind_result($operating_outflows);
        $stmt->fetch();
        $stmt->close();

        // Financing Activities: Capital Contributions (including beginning capital)
        $stmt = $conn->prepare("
            SELECT SUM(jl.debit) AS total
            FROM journal_entries je
            JOIN journal_lines jl ON je.id = jl.entry_id
            JOIN accounts a ON jl.account_id = a.id
            WHERE je.client_id = ?
            AND je.entry_date <= ?
            AND a.name = 'Cash'
            AND jl.entry_id IN (
                SELECT jl2.entry_id
                FROM journal_lines jl2
                JOIN accounts a2 ON jl2.account_id = a2.id
                WHERE a2.type = 'Equity'
                    AND a2.name LIKE '%capital%'
                    AND jl2.credit > 0
            )
        ");

        $stmt->bind_param("is", $client_id, $end_date);
        $stmt->execute();
        $stmt->bind_result($financing_inflows);
        $stmt->fetch();
        $stmt->close();

        $financing_inflows = $financing_inflows ?? 0;

        // Financing Activities: Owner’s Withdrawals
        $stmt = $conn->prepare("
            SELECT SUM(jl.debit - jl.credit) AS total
            FROM journal_entries je
            JOIN journal_lines jl ON je.id = jl.entry_id
            JOIN accounts a ON jl.account_id = a.id
            WHERE je.client_id = ? AND je.entry_date <= ? AND a.name = 'Owner’s Withdrawal'
        ");
        $stmt->bind_param("is", $client_id, $end_date);
        $stmt->execute();
        $stmt->bind_result($financing_outflows);
        $stmt->fetch();
        $stmt->close();

        // Ending Cash Balance
        $stmt = $conn->prepare("
            SELECT SUM(jl.debit - jl.credit) AS cash_balance
            FROM journal_entries je
            JOIN journal_lines jl ON je.id = jl.entry_id
            JOIN accounts a ON jl.account_id = a.id
            WHERE je.client_id = ? AND je.entry_date <= ? AND a.name = 'Cash'
        ");
        $stmt->bind_param("is", $client_id, $end_date);
        $stmt->execute();
        $stmt->bind_result($ending_cash);
        $stmt->fetch();
        $stmt->close();
    }

    $netOperating = $operating_inflows - $operating_outflows;
    $netFinancing = $financing_inflows - $financing_outflows;
    $net_cash_flow = $netOperating + $netFinancing;
    $cash_increase = $ending_cash - $beginning_cash;

    $client_name = '';
    if (!empty($client_id)) {
        $result = $conn->query("SELECT name FROM clients WHERE id = " . (int)$client_id);
        if ($row = $result->fetch_assoc()) {
            $client_name = $row['name'];
        }
    }
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement of Cash Flows</title>
    <link rel="stylesheet" href="../styles/reports/cash_flow.css">
    <link rel="stylesheet" href="../partials/topbar.css">
</head>
<body>

<div class="topbar-container">
    <div class="header">
        <img src="../imgs/csk_logo.png" alt="">
        <h1 style="color: #0B440F">Statement of Cash Flow</h1>
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
            <form id="exportPDFForm" method="POST" action="../process/cash_flow_export.php" target="_blank">
            <input type="hidden" name="html_content" id="html_content">
            <button type="submit" name="export_pdf">Export as PDF</button>
            </form>
                <script>
                document.getElementById('exportPDFForm').addEventListener('submit', function (e) {
                const tableHtml = document.getElementById('cash_flow_table').outerHTML;
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
        
        <div id="cash_flow_table">
            <h2 style="color: #0B440F;">Statement of Cash Flow of <?= htmlspecialchars($client_name) ?> as of <?= htmlspecialchars(date("m-d-Y", strtotime($end_date))) ?></h2>

            <div class="table-container">
                <h4>Cash Flows from Operating Activities</h4>
                <table>
                    <tr><td>Cash Inflows (Sales)</td><td><?= number_format($operating_inflows, 2) ?></td></tr>
                    <tr><td>Cash Outflows (Expenses)</td><td>(<?= number_format($operating_outflows, 2) ?>)</td></tr>
                    <tr class="total">
                        <td>Net Operating Cash Flow</td>
                        <td><?= number_format($netOperating, 2) ?></td>
                    </tr>
                </table>
            </div>

            <div class="table-container">
                <h4>Cash Flows from Financing Activities</h4>
                <table>
                    <tr><td>Owner’s Capital</td><td><?= number_format($financing_inflows, 2) ?></td></tr>
                    <tr><td>Owner’s Withdrawals</td><td>(<?= number_format($financing_outflows, 2) ?>)</td></tr>
                    <tr class="total">
                        <td>Net Financing Cash Flow</td>
                        <td><?= number_format($netFinancing, 2) ?></td>
                    </tr>
                </table>
            </div>

            <div class="table-container">
                <h4 class="text-success">Net Cash Flow & Ending Balance</h4>
                <table class="table table-bordered">
                    <tr>
                        <td>Cash at Beginning of Year (<?php echo date('Y-01-01', strtotime($end_date)); ?>)</td>
                        <td><?php echo number_format($beginning_cash, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Net Increase in Cash</td>
                        <td><?php echo number_format($cash_increase, 2); ?></td>
                    </tr>
                    <tr>
                        <th>Cash at End of Year (<?php echo date('Y-m-d', strtotime($end_date)); ?>)</th>
                        <th><?php echo number_format($ending_cash, 2); ?></th>
                    </tr>
                </table>
            </div>
        </div> 
    <?php endif; ?>
</div>

</body>
</html>

