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

    if ($client_id) {
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

        // Owner's Capital from beginning_capital table
        $stmt = $conn->prepare("
            SELECT amount FROM beginning_capital
            WHERE client_id = ? AND effective_date <= ?
            ORDER BY effective_date DESC LIMIT 1
        ");
        $stmt->bind_param("is", $client_id, $end_date);
        $stmt->execute();
        $stmt->bind_result($beginning_capital);
        $stmt->fetch();
        $stmt->close();


        // 3. Final financing inflows: combine both sources (handle NULLs safely)
        $financing_inflows = $beginning_capital ?? 0;

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
    $reconciliation_difference = $ending_cash - $net_cash_flow;
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
        <h1 style="color: #1ABC9C">Statement of Cash Flow</h1>
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
        <h2>Statement as of <?= htmlspecialchars($end_date) ?></h2>

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
            <h4>Net Cash Flow & Ending Balance</h4>
            <table>
                <tr><td>Net Cash Flow (Operating + Financing)</td><td><?= number_format($net_cash_flow, 2) ?></td></tr>
                <tr><td>Ending Cash Balance</td><td><?= number_format($ending_cash, 2) ?></td></tr>
                <tr class="total">
                    <td>Reconciliation Adjustment</td>
                    <td><?= number_format($reconciliation_difference, 2) ?></td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

