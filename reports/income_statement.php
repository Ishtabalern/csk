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
        SELECT a.name, a.type, SUM(jl.debit) AS total_debit, SUM(jl.credit) AS total_credit
        FROM journal_entries je
        JOIN journal_lines jl ON je.id = jl.entry_id
        JOIN accounts a ON jl.account_id = a.id
        WHERE je.client_id = ? AND je.entry_date BETWEEN ? AND ?
        AND a.type IN ('Income', 'Expense', 'Revenue')
        GROUP BY a.id
    ");
    $stmt->bind_param("iss", $client_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $balance = $row['total_credit'] - $row['total_debit']; // income is credit > debit
        if (strtolower($row['type']) === 'income' || strtolower($row['type']) === 'revenue') {
            $income[] = ['name' => $row['name'], 'amount' => $balance];
            $total_income += $balance;
        } elseif (strtolower($row['type']) === 'expense') {
            $balance = $row['total_debit'] - $row['total_credit']; // expenses are debit > credit
            $expenses[] = ['name' => $row['name'], 'amount' => $balance];
            $total_expenses += $balance;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Income Statement</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <h2>Income Statement</h2>

    <form method="get">
        <label>Client:</label>
        <select name="client_id" required>
            <option value="">Select Client</option>
            <?php while ($c = $clients->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $client_id ? 'selected' : '' ?>><?= $c['name'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>From:</label>
        <input type="date" name="start_date" value="<?= $start_date ?>" required>
        <label>To:</label>
        <input type="date" name="end_date" value="<?= $end_date ?>" required>

        <button type="submit">Generate</button>
    </form>

    <?php if ($client_id): ?>
        <h3>From <?= $start_date ?> to <?= $end_date ?></h3>

        <h4>Income</h4>
        <table border="1" cellpadding="5">
            <tr><th>Account</th><th>Amount</th></tr>
            <?php foreach ($income as $i): ?>
                <tr><td><?= $i['name'] ?></td><td><?= number_format($i['amount'], 2) ?></td></tr>
            <?php endforeach; ?>
            <tr><td><strong>Total Income</strong></td><td><strong><?= number_format($total_income, 2) ?></strong></td></tr>
        </table>

        <h4>Expenses</h4>
        <table border="1" cellpadding="5">
            <tr><th>Account</th><th>Amount</th></tr>
            <?php foreach ($expenses as $e): ?>
                <tr><td><?= $e['name'] ?></td><td><?= number_format($e['amount'], 2) ?></td></tr>
            <?php endforeach; ?>
            <tr><td><strong>Total Expenses</strong></td><td><strong><?= number_format($total_expenses, 2) ?></strong></td></tr>
        </table>

        <h3>
            <?= ($total_income - $total_expenses) >= 0 ? 'Net Profit' : 'Net Loss' ?>:
            <?= number_format($total_income - $total_expenses, 2) ?>
        </h3>
    <?php endif; ?>
    <a href="view_reports.php">← Back to Reports</a>
</body>
</html>
