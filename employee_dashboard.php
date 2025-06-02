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

$recent = $conn->query("SELECT id, vendor, category, amount, receipt_date, created_at FROM receipts ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="styles/employee/employee_dashboard.css">
    <link rel="stylesheet" href="partials/sidebar.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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

        <div class="top-container">
            <div class="section-container">
                <section class="section shortcut" aria-label="Shortcuts">
                    <h2>Shortcuts</h2>
                    <div class="shortcut-container">
                        <a href="receipts/add.php" aria-label="Add Receipt" class="links">
                            <span style="font-size: 50px">🧾</span>                    
                            <label>Add Receipt</label>
                        </a>
                        <a href="receipts/view.php" aria-label="View My Receipts" class="links">
                            <span style="font-size: 50px">📂</span>
                            <label>View My Receipts</label>
                        </a>
                        <a href="reports/all_receipts.php" aria-label="All Receipts Report" class="links">
                            <span style="font-size: 50px">📄</span> 
                            <label>All Receipts Report</label>
                        </a>
                        <a href="process/logout.php" aria-label="Logout" class="links">
                            <span style="font-size: 50px">🚪</span> 
                            <label>Logout</label>
                        </a>
                    </div>
                </section>
            </div>

            <div class="summary-container">
                <h2 style="margin-bottom: 15px;">Summary</h2>
                <div class="box-container">
                    <div class="box">
                        <h3 style="margin-bottom: 30px;">Total Income</h3>
                        <div class="amount income" style="font-size: 50px; font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;">₱<?= number_format($income, 2) ?></div>
                    </div>

                    <div class="box">
                        <h3 style="margin-bottom: 30px;">Total Expenses</h3>
                        <div class="amount expense" style="font-size: 50px; font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;">₱<?= number_format($expense, 2) ?></div>
                    </div>

                    <div class="profit-loss">
                        <h3 style="margin-bottom: 20px; font-size: 40px; font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;">Profit and Loss</h3>
                        <div style="font-size: 50px; font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif; margin-bottom: 20px;" class="amount <?= $profit_loss >= 0 ? 'income' : 'expense' ?>">₱<?= number_format($profit_loss, 2) ?></div>
                        <div class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?= $income ?>" aria-valuenow="<?= $profit_loss > 0 ? $profit_loss : 0 ?>">
                            <div class="bar-fill" style="width:<?= $income > 0 ? min(max(abs($profit_loss / $income) * 100, 0), 100) : 0 ?>%; background-color: <?= $profit_loss >= 0 ? '#4caf50' : '#c62828' ?>;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bottom-container">
            <div class="recent-container">
                <h2>Recent Receipts</h2>
                <table border="1">
                    <tr>
                        <th>Receipt ID</th>
                        <th>Vendor</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Receipt Date</th>
                        <th>Created At</th>
                    </tr>
                    <?php while ($row = $recent->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['vendor']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td>₱<?= number_format((float)$row['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($row['receipt_date']) ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>

            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
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

    $(document).ready(function () {
        $('#receiptTable').DataTable({
            "order": [[5, "desc"]], // Sort by 'Created At' column (index starts at 0)
            "pageLength": 5,
            "lengthMenu": [5, 10, 25, 50, 100],
            "language": {
                "search": "Search Receipts:",
                "lengthMenu": "Show _MENU_ entries",
                "zeroRecords": "No matching receipts found",
                "info": "Showing _START_ to _END_ of _TOTAL_ receipts",
                "infoEmpty": "No receipts available",
                "infoFiltered": "(filtered from _MAX_ total receipts)"
            }
        });
    });
    </script>
</body>
</html>
