<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$selected_year = $_GET['year'] ?? date('Y');
$client_id = $_GET['client_id'] ?? '';

$clients = $conn->query("SELECT id, name FROM clients ORDER BY name ASC");

$monthly_sales = array_fill(1, 12, 0);
$monthly_expenses = array_fill(1, 12, 0);

// Build condition
$whereParts = [];

if (!empty($client_id)) {
    $whereParts[] = "receipts.client_id = " . intval($client_id);
}

if (!empty($startDate)) {
    $whereParts[] = "receipt_date >= '$startDate'";
}

if (!empty($endDate)) {
    $whereParts[] = "receipt_date <= '$endDate'";
}

$whereClause = "";
if (!empty($whereParts)) {
    $whereClause = "WHERE " . implode(" AND ", $whereParts);
}

// Updated SQL to JOIN categories table
$sql = "SELECT 
            MONTH(receipt_date) AS month, 
            categories.type AS category_type, 
            SUM(amount) AS total
        FROM receipts
        LEFT JOIN categories 
            ON receipts.category = categories.name 
            AND receipts.client_id = categories.client_id
        $whereClause
        GROUP BY MONTH(receipt_date), categories.type";


$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $month = (int)$row['month'];
    $category_type = strtolower($row['category_type']);
    $amount = (float)$row['total'];

    if ($category_type === 'income') {
        $monthly_sales[$month] += $amount;
    } elseif ($category_type === 'expense') {
        $monthly_expenses[$month] += $amount;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales vs Expenses</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial; padding: 20px; }
        canvas { max-width: 100%; }
        select, button { padding: 5px 10px; margin-right: 10px; }
        table { margin-top: 30px; border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<?php
$dashboard_link = ($_SESSION['role'] === 'admin') ? '../admin/reports/view_reports.php' : 'view_reports.php';
?>
<a href="<?= $dashboard_link ?>" style="text-decoration:none; background:#007bff; color:white; padding:8px 12px; border-radius:5px;">
    ⬅️ Back to Reports
</a>
<br><br>

<h2>📊 Sales vs Expenses Report</h2>

<?php
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$dateCondition = '';
if (!empty($startDate)) {
    $dateCondition .= " AND receipt_date >= '$startDate'";
}
if (!empty($endDate)) {
    $dateCondition .= " AND receipt_date <= '$endDate'";
}
?>
<form method="GET" class="row g-3 mb-4">

    <div class="col-md-3">
        <label>Start Date</label>
        <input type="date" name="start_date" value="<?= $startDate ?>" class="form-control">
    </div>
    <div class="col-md-3">
        <label>End Date</label>
        <input type="date" name="end_date" value="<?= $endDate ?>" class="form-control">
    </div>

    <div class="col-md-3">
        <label>Client:</label>
        <select name="client_id" class="form-control">
            <option value="">All Clients</option>
            <?php while ($row = $clients->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= $client_id == $row['id'] ? 'selected' : '' ?>>
                    <?= $row['name'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="col-md-3 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">🔍 Apply Filter</button>
    </div>
</form>


<canvas id="salesExpenseChart" height="100"></canvas>

<script>
const ctx = document.getElementById('salesExpenseChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"]) ?>,
        datasets: [
            {
                label: 'Sales',
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                data: <?= json_encode(array_values($monthly_sales)) ?>
            },
            {
                label: 'Expenses',
                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                data: <?= json_encode(array_values($monthly_expenses)) ?>
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Monthly Sales vs Expenses' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => '₱' + value.toLocaleString()
                }
            }
        }
    }
});
</script>

<table>
    <thead>
        <tr>
            <th>Month</th>
            <th>Sales (₱)</th>
            <th>Expenses (₱)</th>
        </tr>
    </thead>
    <tbody>
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <tr>
                <td style="text-align: left"><?= date('F', mktime(0, 0, 0, $m, 10)) ?></td>
                <td><?= number_format($monthly_sales[$m], 2) ?></td>
                <td><?= number_format($monthly_expenses[$m], 2) ?></td>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>

</body>
</html>
