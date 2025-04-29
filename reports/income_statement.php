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

$client_name = '';
if (!empty($client_id)) {
    $result = $conn->query("SELECT name FROM clients WHERE id = " . (int)$client_id);
    if ($row = $result->fetch_assoc()) {
        $client_name = $row['name'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Income Statement</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../styles/reports/income_statement.css">
</head>
<body>
    <h1 style="justify-self:center">Income Statement</h1>

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
        <div class="incomeStatement-container">
            <h3>From <?= $start_date ?> to <?= $end_date ?></h3>
            <div class="customer-name"> 
                
                <p><?= htmlspecialchars($client_name) ?></p>
                <h3 id="tab-content">Income Statement</h3>
            </div>

            <div class="table">
                <table border="1" cellpadding="5">
                    <tr><td class="left bold">Income</td></tr>
                    <tr><th>Account</th><th>Amount</th></tr>
                    <?php foreach ($income as $i): ?>
                        <tr><td><?= $i['name'] ?></td><td><?= number_format($i['amount'], 2) ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td><strong>Total Income</strong></td><td><strong><?= number_format($total_income, 2) ?></strong></td></tr>
                </table>
            </div>

            <h4>Expenses</h4>
            <div class="table">
                <table border="1" cellpadding="5">
                    <tr><td class="left bold">Expense</td></tr>
                    <tr><th>Account</th><th>Amount</th></tr>
                    <?php foreach ($expenses as $e): ?>
                        <tr><td><?= $e['name'] ?></td><td><?= number_format($e['amount'], 2) ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td><strong>Total Expenses</strong></td><td><strong><?= number_format($total_expenses, 2) ?></strong></td></tr>
                </table>
            </div>

            <h3>
                <?= ($total_income - $total_expenses) >= 0 ? 'Net Profit' : 'Net Loss' ?>:
                <?= number_format($total_income - $total_expenses, 2) ?>
            </h3>

            <div class="btn">
                <?php
                $dashboard_link = ($_SESSION['role'] === 'admin') ? '../admin_dashboard.php' : '../employee_dashboard.php';
                ?>
                <a href="<?= $dashboard_link ?>" style="text-decoration:none; background:#007bff; color:white; padding:8px 12px; border-radius:5px; margin-top:20px;">
                    ⬅️ Back to Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>
    
        
      
</body>
</html>
