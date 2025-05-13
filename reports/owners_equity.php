<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$client_id = $_GET['client_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_capital'])) {
    $amount = floatval($_POST['capital_amount']);
    $effective_date = $_POST['effective_date'];
    $client_id = intval($_POST['client_id']);

    // Insert into beginning_capital table
    $stmt = $conn->prepare("INSERT INTO beginning_capital (client_id, amount, effective_date) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $client_id, $amount, $effective_date);
    $stmt->execute();

    // ✅ Also insert into journal_entries
    $desc = "Declared Beginning Capital";
    $entry_stmt = $conn->prepare("INSERT INTO journal_entries (client_id, entry_date, description, created_at) VALUES (?, ?, ?, NOW())");
    $entry_stmt->bind_param("iss", $client_id, $effective_date, $desc);
    $entry_stmt->execute();
    $entry_id = $entry_stmt->insert_id;

    // ✅ Determine the capital account (credit side)
    $capital_account = $conn->query("SELECT id FROM accounts WHERE type = 'Equity' AND name LIKE '%capital%' LIMIT 1")->fetch_assoc();
    $capital_account_id = $capital_account['id'] ?? null;

    // ✅ Determine the cash account (debit side)
    $cash_account = $conn->query("SELECT id FROM accounts WHERE type = 'Asset' AND name LIKE '%cash%' LIMIT 1")->fetch_assoc();
    $cash_account_id = $cash_account['id'] ?? null;

    if ($capital_account_id && $cash_account_id) {
        // Insert debit line (Cash)
        $line_stmt = $conn->prepare("INSERT INTO journal_lines (entry_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");
        $zero = 0.00;
        $line_stmt->bind_param("iidd", $entry_id, $cash_account_id, $amount, $zero);
        $line_stmt->execute();

        // Insert credit line (Capital)
        $line_stmt->bind_param("iidd", $entry_id, $capital_account_id, $zero, $amount);
        $line_stmt->execute();
    }

    echo "<p style='color:green;'>✅ Beginning capital saved for client ID $client_id and posted to journal.</p>";
}



$clients = $conn->query("SELECT id, name FROM clients ORDER BY name");

$conditions = [];
if (!empty($client_id)) {
    $conditions[] = "je.client_id = " . intval($client_id);
}
if (!empty($start_date)) {
    $conditions[] = "je.entry_date >= '" . $conn->real_escape_string($start_date) . "'";
}
if (!empty($end_date)) {
    $conditions[] = "je.entry_date <= '" . $conn->real_escape_string($end_date) . "'";
}
$where_sql = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get Capital Account (first account with 'capital' in name)
$capital_account = $conn->query("SELECT id FROM accounts WHERE type = 'Equity' AND name LIKE '%capital%' LIMIT 1")->fetch_assoc();
$capital_account_id = $capital_account['id'] ?? null;

// Beginning Capital (before start date)
$beginning_capital = 0;

if (!empty($client_id) && !empty($start_date)) {
    // Try manually declared capital first
    $manual_res = $conn->query("
        SELECT amount 
        FROM beginning_capital 
        WHERE client_id = $client_id AND effective_date <= '$start_date'
        ORDER BY effective_date DESC 
        LIMIT 1
    ");

    if ($manual_res && $manual = $manual_res->fetch_assoc()) {
        $beginning_capital = $manual['amount'];
    } elseif ($capital_account_id) {
        // Fallback to auto
        $begin_capital_sql = "
            SELECT 
                SUM(jl.debit) AS total_debit, 
                SUM(jl.credit) AS total_credit
            FROM journal_lines jl
            JOIN journal_entries je ON jl.entry_id = je.id
            WHERE jl.account_id = $capital_account_id
              AND je.client_id = $client_id
              AND je.entry_date < '$start_date'
        ";
        $begin_result = $conn->query($begin_capital_sql)->fetch_assoc();
        $beginning_capital = ($begin_result['total_credit'] ?? 0) - ($begin_result['total_debit'] ?? 0);
    }
}


// Net Income (same logic as income statement)
$income_sql = "
    SELECT 
        SUM(CASE WHEN a.type = 'Revenue' THEN jl.credit - jl.debit ELSE 0 END) -
        SUM(CASE WHEN a.type = 'Expense' THEN jl.debit - jl.credit ELSE 0 END) AS net_income
    FROM journal_lines jl
    JOIN journal_entries je ON jl.entry_id = je.id
    JOIN accounts a ON jl.account_id = a.id
    $where_sql
";
$income_result = $conn->query($income_sql)->fetch_assoc();
$net_income = $income_result['net_income'] ?? 0;

// Withdrawals (assume any account with 'drawing' or 'withdrawal' in name)
$withdrawal_sql = "
    SELECT SUM(jl.debit) AS total_withdrawals
    FROM journal_lines jl
    JOIN journal_entries je ON jl.entry_id = je.id
    JOIN accounts a ON jl.account_id = a.id
    $where_sql AND (a.name LIKE '%withdraw%' OR a.name LIKE '%drawing%')
";
$withdrawals_result = $conn->query($withdrawal_sql)->fetch_assoc();
$total_withdrawals = $withdrawals_result['total_withdrawals'] ?? 0;

$ending_capital = $beginning_capital + $net_income - $total_withdrawals;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Statement of Owner's Equity</title>
    <link rel="stylesheet" href="../styles/reports/owners_equity.css">
    <link rel="stylesheet" href="../partials/topbar.css">
</head>
<body>


<div class="topbar-container">
    <div class="header">
        <img src="../imgs/csk_logo.png" alt="">
        <h1 style="color: #1ABC9C">Statement of Owner’s Equity</h1>
    </div>
    
    <div class="btn">
        <?php
            $dashboard_link = ($_SESSION['role'] === 'admin') ? '../admin/reports/view_reports.php' : 'view_reports.php';
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

    <?php if ($_SESSION['role'] === 'admin' && !empty($client_id)): ?>
        <form method="POST" style="margin-top:30px;">
            <h3>💼 Declare Beginning Capital</h3>
            <label>Amount (₱):</label>
            <input type="number" name="capital_amount" step="0.01" required>
            <label>Effective Date:</label>
            <input type="date" name="effective_date" value="<?= $start_date ?>" required>
            <input type="hidden" name="client_id" value="<?= $client_id ?>">
            <button type="submit" name="set_capital">💾 Save</button>
        </form>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <tr><th class="left">Item</th><th>Amount (₱)</th></tr>
            <tr><td class="left">Beginning Capital</td><td><?= number_format($beginning_capital, 2) ?></td></tr>
            <tr><td class="left">Add: Net Income</td><td><?= number_format($net_income, 2) ?></td></tr>
            <tr><td class="left">Less: Withdrawals</td><td><?= number_format($total_withdrawals, 2) ?></td></tr>
            <tr><th class="left">Ending Capital</th><th><?= number_format($ending_capital, 2) ?></th></tr>
        </table>
    </div>
</div>
</body>
</html>
