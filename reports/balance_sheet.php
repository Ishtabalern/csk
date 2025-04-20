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
            a.id, a.name, a.type, a.subtype,
            SUM(CASE WHEN jl.debit IS NOT NULL THEN jl.debit ELSE 0 END) as total_debit,
            SUM(CASE WHEN jl.credit IS NOT NULL THEN jl.credit ELSE 0 END) as total_credit
        FROM accounts a
        LEFT JOIN journal_lines jl ON jl.account_id = a.id
        LEFT JOIN journal_entries je ON jl.entry_id = je.id
        WHERE je.client_id = ? AND je.entry_date <= ?
        GROUP BY a.id
    ");
    $stmt->bind_param("is", $client_id, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $balance = $row['total_debit'] - $row['total_credit'];
        if ($row['type'] === 'liability' || $row['type'] === 'equity') {
            $balance = $row['total_credit'] - $row['total_debit'];
        }
        $accounts_data[$row['type']][] = [
            'name' => $row['name'],
            'subtype' => $row['subtype'],
            'balance' => $balance
        ];
        $totals[$row['type']] += $balance;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Balance Sheet</title>
    <style>
        body { font-family: Arial; }
        .section { margin: 20px 0; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #008000; color: white; text-align: left; }
        .total { font-weight: bold; }
    </style>
</head>
<body>

<h2>Balance Sheet</h2>

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
    <h3>As of <?= htmlspecialchars($end_date) ?></h3>

    <div class="section">
        <h4>Assets</h4>
        <table>
            <tr><th>Account</th><th>Amount</th></tr>
            <?php foreach ($accounts_data['asset'] ?? [] as $acc): ?>
                <tr>
                    <td><?= htmlspecialchars($acc['name']) ?></td>
                    <td><?= number_format($acc['balance'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total"><td>Total Assets</td><td><?= number_format($totals['assets'], 2) ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h4>Liabilities</h4>
        <table>
            <tr><th>Account</th><th>Amount</th></tr>
            <?php foreach ($accounts_data['liability'] ?? [] as $acc): ?>
                <tr>
                    <td><?= htmlspecialchars($acc['name']) ?></td>
                    <td><?= number_format($acc['balance'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total"><td>Total Liabilities</td><td><?= number_format($totals['liabilities'], 2) ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h4>Equity</h4>
        <table>
            <tr><th>Account</th><th>Amount</th></tr>
            <?php foreach ($accounts_data['equity'] ?? [] as $acc): ?>
                <tr>
                    <td><?= htmlspecialchars($acc['name']) ?></td>
                    <td><?= number_format($acc['balance'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total"><td>Total Equity</td><td><?= number_format($totals['equity'], 2) ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h4>Total Liabilities & Equity</h4>
        <table>
            <tr><td class="total">Total</td><td class="total"><?= number_format($totals['liabilities'] + $totals['equity'], 2) ?></td></tr>
        </table>
    </div>
<?php endif; ?>

</body>
</html>
