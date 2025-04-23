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
$capital_account = $conn->query("SELECT id FROM accounts WHERE name LIKE '%capital%' LIMIT 1")->fetch_assoc();
$capital_account_id = $capital_account['id'] ?? null;

// Beginning Capital (before start date)
$beginning_capital = 0;
if (!empty($client_id) && !empty($start_date) && $capital_account_id) {
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


// Net Income (same logic as income statement)
$income_sql = "
    SELECT 
        SUM(CASE WHEN a.type = 'income' THEN jl.credit - jl.debit ELSE 0 END) -
        SUM(CASE WHEN a.type = 'expense' THEN jl.debit - jl.credit ELSE 0 END) AS net_income
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
    $where_sql AND a.name LIKE '%withdrawal%'
";
$withdrawals_result = $conn->query($withdrawal_sql)->fetch_assoc();
$total_withdrawals = $withdrawals_result['total_withdrawals'] ?? 0;

$ending_capital = $beginning_capital + $net_income - $total_withdrawals;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Statement of Owner's Equity</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: right; }
        th.left, td.left { text-align: left; }
    </style>
</head>
<body>

<h2>📗 Statement of Owner’s Equity</h2>

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

<table>
    <tr><th class="left">Item</th><th>Amount (₱)</th></tr>
    <tr><td class="left">Beginning Capital</td><td><?= number_format($beginning_capital, 2) ?></td></tr>
    <tr><td class="left">Add: Net Income</td><td><?= number_format($net_income, 2) ?></td></tr>
    <tr><td class="left">Less: Withdrawals</td><td><?= number_format($total_withdrawals, 2) ?></td></tr>
    <tr><th class="left">Ending Capital</th><th><?= number_format($ending_capital, 2) ?></th></tr>
</table>

</body>
</html>
