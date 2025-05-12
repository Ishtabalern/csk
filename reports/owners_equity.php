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

    $stmt = $conn->prepare("INSERT INTO beginning_capital (client_id, amount, effective_date) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $client_id, $amount, $effective_date);
    $stmt->execute();

    echo "<p style='color:green;'>✅ Beginning capital saved for client ID $client_id.</p>";
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
    <link rel="stylesheet" href="../partials/topbar.css">
    <style>
        * {
        margin: 0;
        padding: 0;
        list-style: none;
        text-decoration: none;
        box-sizing: border-box;
        scroll-behavior: smooth;
        font-family: Arial, sans-serif;
        }
        .container{padding: 20px;}
        select, input[type="date"], button { margin: 5px; padding: 5px 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px;}
        th, td { padding: 10px; border: 1px solid #ccc; text-align: right; }
        th.left, td.left { text-align: left; }
    </style>
</head>
<body>


<div class="topbar-container">
    <div class="header">
        <img src="../imgs/csk_logo.png" alt="">
        <h1 style="color: #1ABC9C">Statement of Owner’s Equity</h1>
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

    <form method="GET">
        <label>Client:</label>
        <select name="client_id">
            <option value="">All Clients</option>
            <?php while ($row = $clients->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= ($row['id'] == $client_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Start Date:</label>
        <input type="date" name="start_date" value="<?= $start_date ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" value="<?= $end_date ?>">

        <button type="submit">View</button>
    </form>

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

    <table>
        <tr><th class="left">Item</th><th>Amount (₱)</th></tr>
        <tr><td class="left">Beginning Capital</td><td><?= number_format($beginning_capital, 2) ?></td></tr>
        <tr><td class="left">Add: Net Income</td><td><?= number_format($net_income, 2) ?></td></tr>
        <tr><td class="left">Less: Withdrawals</td><td><?= number_format($total_withdrawals, 2) ?></td></tr>
        <tr><th class="left">Ending Capital</th><th><?= number_format($ending_capital, 2) ?></th></tr>
    </table>

</div>
</body>
</html>
