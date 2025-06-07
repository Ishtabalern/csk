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

// Fetch client name
$client_name = '';
if ($client_id) {
    $client_stmt = $conn->prepare("SELECT name FROM clients WHERE id = ?");
    $client_stmt->bind_param("i", $client_id);
    $client_stmt->execute();
    $client_stmt->bind_result($client_name);
    $client_stmt->fetch();
    $client_stmt->close();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Trial Balance</title>
    <link rel="stylesheet" href="../partials/topbar.css">
    <link rel="stylesheet" href="../styles/reports/trial_balance.css">
</head>
<body>

<?php
$dashboard_link = ($_SESSION['role'] === 'admin') ? '../admin/reports/view_reports.php' : 'view_reports.php';
?>

    <div class="topbar-container">
        <div class="header">
            <img src="../imgs/csk_logo.png" alt="">
            <h1 style="color:#0B440F">Trial Balance Report</h1>
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
    
    <?php if ($client_id): ?>
        <div class="exports-btn">
            <form id="exportPDFForm" method="POST" action="../process/trial_balance_export.php" target="_blank">
            <input type="hidden" name="html_content" id="html_content">
            <button type="submit" name="export_pdf">Export as PDF</button>
            </form>
                <script>
                document.getElementById('exportPDFForm').addEventListener('submit', function (e) {
                const tableHtml = document.getElementById('trial_balance_table').outerHTML;
                document.getElementById('html_content').value = tableHtml;
                });
                </script>

            <form id="exportExcelForm" method="POST" action="../process/balance_export_excel.php">
            <input type="hidden" name="client_id" value="<?= $client_id ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            </form>
                <script>
                document.getElementById('exportExcelForm').addEventListener('submit', function (e) {
                const tableHtml = document.getElementById('balance_sheet_table').outerHTML;
                document.getElementById('excel_html_content').value = tableHtml;
                });
                </script>
        </div>

        <div class="table-container" id="trial_balance_table">
            <h2 style="margin-top:15px; color:#0B440F; text-align:center;">Trial Balance Report of <?= htmlspecialchars($client_name) ?> from <?= htmlspecialchars(date("m-d-Y", strtotime($start_date))) ?> to <?= htmlspecialchars(date("m-d-Y", strtotime($end_date))) ?></h2>
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
        </div>
    <?php endif; ?>

 
    <?php if ($total_debit !== $total_credit): ?>
        <p class="warning">⚠️ Trial Balance is not balanced!</p>
    <?php endif; ?>

</div>



</body>
</html>
