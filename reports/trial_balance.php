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
        a.id AS account_id,
        a.name AS account_name,
        a.code AS account_code,
        a.type AS account_type,
        SUM(CASE WHEN jl.debit > 0 THEN jl.debit ELSE 0 END) AS total_debit,
        SUM(CASE WHEN jl.credit > 0 THEN jl.credit ELSE 0 END) AS total_credit
    FROM journal_lines jl
    JOIN journal_entries je ON jl.entry_id = je.id
    JOIN accounts a ON jl.account_id = a.id
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
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
        td.left { text-align: left; }
        .warning { color: red; font-weight: bold; }
    </style>
</head>
<body>

<?php
$dashboard_link = ($_SESSION['role'] === 'admin') ? '../admin/reports/view_reports.php' : 'view_reports.php';
?>

    <div class="topbar-container">
        <div class="header">
            <img src="../imgs/csk_logo.png" alt="">
            <h1 style="color:#1ABC9C">Trial Balance Report</h1>
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

        <button type="submit">🔍 Filter</button>
    </form>

    <table>
        <thead>
            <tr>
                <th class="left">Account Code</th>
                <th class="left">Account Name</th>
                <th class="left">Type</th>
                <th>Debit (₱)</th>
                <th>Credit (₱)</th>
                <th>Running Debit</th>
                <th>Running Credit</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $running_debit = 0;
            $running_credit = 0;
            foreach ($rows as $r): 
                $running_debit += $r['total_debit'];
                $running_credit += $r['total_credit'];
            ?>
                <tr>
                    <td class="left"><?= htmlspecialchars($r['account_code']) ?></td>
                    <td class="left"><?= htmlspecialchars($r['account_name']) ?></td>
                    <td class="left"><?= htmlspecialchars($r['account_type']) ?></td>
                    <td><?= number_format($r['total_debit'], 2) ?></td>
                    <td><?= number_format($r['total_credit'], 2) ?></td>
                    <td><?= number_format($running_debit, 2) ?></td>
                    <td><?= number_format($running_credit, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th colspan="3">TOTAL</th>
                <th><?= number_format($total_debit, 2) ?></th>
                <th><?= number_format($total_credit, 2) ?></th>
                <th colspan="2"></th>
            </tr>
        </tbody>
    </table>

    <?php if ($total_debit !== $total_credit): ?>
        <p class="warning">⚠️ Trial Balance is not balanced!</p>
    <?php endif; ?>

</div>



</body>
</html>
