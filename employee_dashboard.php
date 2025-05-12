<?php
session_start();
if ($_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit;
}

include 'includes/db.php';

$client_id = $_GET['client_id'] ?? '';
$year = date('Y');

// Fetch all clients for dropdown
$clients = $conn->query("SELECT id, name FROM clients ORDER BY name");

// Fetch income and expense totals
$sql = "SELECT 
            categories.type AS category_type, 
            SUM(amount) AS total
        FROM receipts
        LEFT JOIN categories 
            ON receipts.category = categories.name AND receipts.client_id = categories.client_id
        WHERE YEAR(receipt_date) = ? AND receipts.client_id = ?
        GROUP BY categories.type";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $year, $client_id);
$stmt->execute();
$result = $stmt->get_result();

$income = 0;
$expense = 0;

while ($row = $result->fetch_assoc()) {
    if (strtolower($row['category_type']) === 'income') {
        $income += (float)$row['total'];
    } elseif (strtolower($row['category_type']) === 'expense') {
        $expense += (float)$row['total'];
    }
}

$profit_loss = $income - $expense;
?>

<!DOCTYPE html>                     
<html>
<head>
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="styles/employee/employee_dashboard.css">
    <link rel="stylesheet" href="partials/sidebar.css">
</head>
<body>
    <?php
        $page = 'employee_dashboard';
        include 'partials/sidebar.php'; 
    ?>

    <main class="dashboard" role="main">
        <h1 class="dashboard-header">Employee Dashboard</h1>

        <div class="client-dropdown">
            <form method="get">
                <label for="clientFilter">Choose Client:</label>
                <select name="client_id" id="clientFilter" onchange="this.form.submit()">
                    <option value="">All Clients</option>
                    <?php while ($row = $clients->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= ($row['id'] == $client_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <div class="container">
            <section class="section shortcut" aria-label="Shortcuts">
                <h2>Shortcuts</h2>
                <div class="shortcut-container">
                    <a href="receipts/add.php" aria-label="Add Receipt">
                        <span>🧾</span>                    
                        <label>Add Receipt</label>
                    </a>
                    <a href="receipts/view.php" aria-label="View My Receipts">
                        <span>📂</span>
                        <label>View My Receipts</label>
                    </a>
                    <a href="reports/all_receipts.php" aria-label="All Receipts Report">
                        <span>📄</span> 
                        <label>All Receipts Report</label>
                    </a>
                    <a href="process/logout.php" aria-label="Logout">
                        <span>🚪</span> 
                        <label>Logout</label>
                    </a>
                </div>
            </section>

            <section class="section task" aria-label="Tasks">
                <h2>Task</h2>
                <div class="task-container">
                    <!-- Placeholder for future content -->
                </div>
            </section>
        </div>

        <div class="container">
            <div class="box-container">
                <div class="box">
                    <h3>Profit and Loss</h3>
                    <div class="amount <?= $profit_loss >= 0 ? 'income' : 'expense' ?>">₱<?= number_format($profit_loss, 2) ?></div>
                    <div class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?= $income ?>" aria-valuenow="<?= $profit_loss > 0 ? $profit_loss : 0 ?>">
                        <div class="bar-fill" style="width:<?= $income > 0 ? min(max(($profit_loss/$income)*100,0),100) : 0 ?>%; background-color: <?= $profit_loss >= 0 ? '#4caf50' : '#c62828' ?>;"></div>
                    </div>
                </div>

                <div class="box">
                    <h3>Total Income</h3>
                    <div class="amount income">₱<?= number_format($income, 2) ?></div>
                </div>

                <div class="box">
                    <h3>Total Expenses</h3>
                    <div class="amount expense">₱<?= number_format($expense, 2) ?></div>
                </div>

                <div class="box">
                    <h3>Invoices</h3>
                    <canvas id="invoiceChart" aria-label="Invoice payment status chart" role="img" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('invoiceChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Paid', 'Unpaid'],
                    datasets: [{
                        data: [<?= $paid ?? 0 ?>, <?= $unpaid ?? 0 ?>],
                        backgroundColor: ['#4caf50', '#f44336'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        });
    </script>
</body>

</html>