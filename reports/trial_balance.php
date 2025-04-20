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

// Fetch all clients for dropdown
$clients = $conn->query("SELECT id, name FROM clients ORDER BY name");

// Build date filter condition
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

// Fetch accounts and calculate balances
$sql = "
    SELECT 
        a.code, a.name,
        SUM(COALESCE(jl.debit, 0)) AS total_debit,
        SUM(COALESCE(jl.credit, 0)) AS total_credit
    FROM accounts a
    LEFT JOIN journal_lines jl ON jl.account_id = a.id
    LEFT JOIN journal_entries je ON je.id = jl.entry_id
    $where_sql
    GROUP BY a.id
    ORDER BY a.code
";

$result = $conn->query($sql);
$rows = [];
$total_debit = $total_credit = 0;

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
    $total_debit += $row['total_debit'];
    $total_credit += $row['total_credit'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trial Balance</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        select, input[type="date"], button { margin: 5px; padding: 5px 10px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
        td.left { text-align: left; }
        .warning { color: red; font-weight: bold; }
    </style>
</head>
<body>

<?php
$dashboard_link = ($_SESSION['role'] === 'admin') ? '../admin_dashboard.php' : 'view_reports.php';
?>
<a href="<?= $dashboard_link ?>" style="text-decoration:none; background:#007bff; color:white; padding:8px 12px; border-radius:5px;">
    ⬅️ Back to Reports
</a>
<br><br>

<h2>📘 Trial Balance Report</h2>

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

    <button type="submit">🔍 Filter</button>
</form>

<table>
    <thead>
        <tr>
            <th class="left">Account Code</th>
            <th class="left">Account Name</th>
            <th>Debit (₱)</th>
            <th>Credit (₱)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td class="left"><?= htmlspecialchars($r['code']) ?></td>
                <td class="left"><?= htmlspecialchars($r['name']) ?></td>
                <td><?= number_format($r['total_debit'], 2) ?></td>
                <td><?= number_format($r['total_credit'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <th colspan="2">TOTAL</th>
            <th><?= number_format($total_debit, 2) ?></th>
            <th><?= number_format($total_credit, 2) ?></th>
        </tr>
    </tbody>
</table>

<?php if ($total_debit !== $total_credit): ?>
    <p class="warning">⚠️ Trial Balance is not balanced!</p>
<?php endif; ?>

</body>
</html>
