<?php
session_start();
include '../includes/db.php';

$clients = $conn->query("SELECT id, name FROM clients");
$client_id = $_GET['client_id'] ?? null;
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$operating_inflows = 0; // Sales
$operating_outflows = 0; // Expenses
$financing_inflows = 0; // Owner’s Capital
$financing_outflows = 0; // Owner’s Withdrawals
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
        WHERE je.client_id = ? AND je.entry_date <= ? AND a.type = 'Expense'
    ");
    $stmt->bind_param("is", $client_id, $end_date);
    $stmt->execute();
    $stmt->bind_result($operating_outflows);
    $stmt->fetch();
    $stmt->close();

    // Financing Activities: Owner’s Capital
    $stmt = $conn->prepare("
        SELECT SUM(jl.credit - jl.debit) AS total
        FROM journal_entries je
        JOIN journal_lines jl ON je.id = jl.entry_id
        JOIN accounts a ON jl.account_id = a.id
        WHERE je.client_id = ? AND je.entry_date <= ? AND a.name = 'Owner’s Capital'
    ");
    $stmt->bind_param("is", $client_id, $end_date);
    $stmt->execute();
    $stmt->bind_result($financing_inflows);
    $stmt->fetch();
    $stmt->close();

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

$net_cash_flow = ($operating_inflows - $operating_outflows) + ($financing_inflows - $financing_outflows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

<form method="get">
    <label>Client:
        <select name="client_id" required>
            <option value="">Select client</option>
            <?php while ($row = $clients->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= ($client_id == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['name']) ?></option>
            <?php endwhile; ?>
        </select>
    </label>
    <label>Date:
        <input type="date" name="end_date" value="<?= $end_date ?>">
    </label>
    <button type="submit">Generate</button>
</form>

<?php if ($client_id): ?>
<h3>Statement of Cash Flows (As of <?= htmlspecialchars($end_date) ?>)</h3>

<div class="section">
    <h4>Cash Flows from Operating Activities</h4>
    <table>
        <tr><td>Cash Inflows (Sales)</td><td><?= number_format($operating_inflows, 2) ?></td></tr>
        <tr><td>Cash Outflows (Expenses)</td><td>(<?= number_format($operating_outflows, 2) ?>)</td></tr>
        <tr class="total"><td>Net Operating Cash Flow</td><td><?= number_format($operating_inflows - $operating_outflows, 2) ?></td></tr>
    </table>
</div>

<div class="section">
    <h4>Cash Flows from Financing Activities</h4>
    <table>
        <tr><td>Owner’s Capital</td><td><?= number_format($financing_inflows, 2) ?></td></tr>
        <tr><td>Owner’s Withdrawal</td><td>(<?= number_format($financing_outflows, 2) ?>)</td></tr>
        <tr class="total"><td>Net Financing Cash Flow</td><td><?= number_format($financing_inflows - $financing_outflows, 2) ?></td></tr>
    </table>
</div>

<div class="section">
    <h4>Net Cash Flow & Ending Balance</h4>
    <table>
        <tr><td>Net Cash Flow</td><td><?= number_format($net_cash_flow, 2) ?></td></tr>
        <tr><td>Ending Cash Balance</td><td><?= number_format($ending_cash, 2) ?></td></tr>
    </table>
</div>
<?php endif; ?>
    
</body>
</html>
