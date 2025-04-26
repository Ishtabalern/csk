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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Income Statement</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../styles/reports/income_statement.css">
</head>
<body>
    <h1>Income Statement</h1>

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
        <h3>From <?= $start_date ?> to <?= $end_date ?></h3>

        <h4>Income</h4>
        <table border="1" cellpadding="5">
            <tr><th>Account</th><th>Amount</th></tr>
            <?php foreach ($income as $i): ?>
                <tr><td><?= $i['name'] ?></td><td><?= number_format($i['amount'], 2) ?></td></tr>
            <?php endforeach; ?>
            <tr><td><strong>Total Income</strong></td><td><strong><?= number_format($total_income, 2) ?></strong></td></tr>
        </table>

        <h4>Expenses</h4>
        <table border="1" cellpadding="5">
            <tr><th>Account</th><th>Amount</th></tr>
            <?php foreach ($expenses as $e): ?>
                <tr><td><?= $e['name'] ?></td><td><?= number_format($e['amount'], 2) ?></td></tr>
            <?php endforeach; ?>
            <tr><td><strong>Total Expenses</strong></td><td><strong><?= number_format($total_expenses, 2) ?></strong></td></tr>
        </table>

        <h3>
            <?= ($total_income - $total_expenses) >= 0 ? 'Net Profit' : 'Net Loss' ?>:
            <?= number_format($total_income - $total_expenses, 2) ?>
        </h3>
    <?php endif; ?>

    <div class="incomeStatement-container">
        <div class="customer-name"> 
            <p>Customer Name</p>
            <h3 id="tab-content">Income Statement</h3>
        </div>
                        
        <div class="table">
            <table>
                <tr>
                    <td class="left bold top"></td>
                    <td class="top"></td>
                </tr>
                <tr>
                    <td class="left bold">Income</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="middle">Net Income</td>
                    <td class="right bold">20</td>
                </tr>
                <tr>
                    <td class="middle">Deferred Tax</td>
                    <td class="right bold">₱ 1,000</td>
                </tr>
                <tr>
                    <td class="middle">Depredation</td>
                    <td class="right">(₱ 119,000)</td>
                </tr>
                <tr>
                    <td class="middle" style="text-indent: 40px;">Cash from Accounts Receivable</td>
                    <td class="right">(₱ 119,000)</td>
                </tr>
                <tr>
                    <td class="middle" style="text-indent: 40px;">Cash from Inventory</td>
                    <td class="right">326,414</td>
                </tr>
                <tr>
                    <td class="middle" style="text-indent: 40px;">Cash from Accounts Payable</td>
                    <td class="right">123123123</td>
                </tr>
                <tr>
                    <td class="middle bottom">Total Income</td>
                    <td class="right bottom">123123123</td>
                </tr>
                <tr>
                    <td class="left bold">Expenses</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="middle">Andoks manoy</td>
                    <td class="right">69</td>
                </tr>
                <tr>
                    <td class="middle bottom">Total expenses</td>
                    <td class="right bottom">69</td>
                </tr>
                <tr>
                    <td class="middle bottom">Income before taxes</td>
                    <td class="right bottom">69</td>
                </tr>
                <tr>
                    <td class="middle bottom">Income tax expense</td>
                    <td class="right bottom">69</td>
                </tr>
                <tr>
                    <td class="middle bottom">Net income</td>
                    <td class="right bottom">69</td>
                </tr>
          

                
            </table>
        </div>
        
        <div class="btn">
            <a href="view_reports.php">← Back to Reports</a>
        </div>
       
    </div>
    
    
    
    
</body>
</html>
