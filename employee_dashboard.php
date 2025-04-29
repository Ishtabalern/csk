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

    <div class="dashboard">
        <h1 class="dashboard-header">Employee Dashboard</h1>

        <div class="client-dropdown">
        <form method="get">
            <label for="clientFilter">Choose Client: </label>
            <select name="client_id" onchange="this.form.submit()">
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
            <div class="section shortcut">
                <h2>Shortcuts</h2>
                <div class="shortcut-container">
                    <a href="receipts/add.php">
                        <span>🧾</span>                    
                        <label for="">Add Receipt</label>
                    </a>

                    <a href="receipts/view.php">
                        <span>📂</span>
                        <label for="">View My Receipts</label>
                    </a>

                    <a href="reports/all_receipts.php">
                        <span>📄</span> 
                        <label for="">All Receipts Report</label>
                    </a>

                    <a href="process/logout.php">
                        <span>🚪</span> 
                        <label for="">Logout</label>
                    </a>
                </div>
            </div>

            <div class="section task">
                <h2>Task</h2>
                <div class="task-container">

                </div>
            </div>
        </div>

        <div class="container">
            <div class="box-container">
                <div class="box">
                    <div class="card">
                        <h3>Profit and Loss</h3>
                        <div class="amount">₱<?= number_format($profit_loss, 2) ?></div>
                        <div class="bar">
                            <div class="bar-fill" style="width:<?= $income > 0 ? ($profit_loss / $income) * 100 : 0 ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="box">
                    <div class="card">
                        <h3>Total Income</h3>
                        <div class="amount" style="color:green;">₱<?= number_format($income, 2) ?></div>
                    </div>
                </div>

                <div class="box">
                    <div class="card">
                        <h3>Total Expenses</h3>
                        <div class="amount" style="color:#c62828;">₱<?= number_format($expense, 2) ?></div>
                    </div>
                </div>

                <div class="box">
                    <h1>Invoices</h1>
                    <span>amount</span>
                </div>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('invoiceChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Unpaid'],
            datasets: [{
                data: [<?= $paid ?>, <?= $unpaid ?>],
                backgroundColor: ['#4caf50', '#f44336'],
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    </script>
</body>
</html>