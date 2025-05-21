<?php
function generateBalanceSheetData($conn, $client_id, $end_date) {
    $accounts_data = ['Assets' => [], 'Liabilities' => [], 'Equity' => []];
    $totals = ['assets' => 0, 'liabilities' => 0];
    $beginning_capital = 0;
    $net_income = 0;
    $withdrawals = 0;

    // --- 1. Get Assets & Liabilities
    $sql = "
    SELECT a.name, a.type,
           SUM(IFNULL(jl.debit, 0)) - SUM(IFNULL(jl.credit, 0)) AS balance
    FROM accounts a
    JOIN journal_lines jl ON jl.account_id = a.id
    JOIN journal_entries je ON je.id = jl.entry_id
    WHERE a.client_id = ? AND je.entry_date <= ? AND a.type IN ('Asset', 'Liability')
    GROUP BY a.id
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $client_id, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $type = ucfirst(strtolower($row['type'])); // Asset or Liability
        $account = ['account' => $row['name'], 'amount' => floatval($row['balance'])];
        $accounts_data[$type . 's'][] = $account;
        $totals[strtolower($type) . 's'] += $account['amount'];
    }

    // --- 2. Get Beginning Capital
    $cap_sql = "SELECT amount FROM beginning_capital WHERE client_id = ? AND effective_date <= ? ORDER BY effective_date DESC LIMIT 1";
    $stmt = $conn->prepare($cap_sql);
    $stmt->bind_param('is', $client_id, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $beginning_capital = floatval($row['amount']);
    }

    // --- 3. Get Net Income
    $ni_sql = "
    SELECT
        SUM(CASE WHEN a.type = 'Revenue' THEN IFNULL(jl.credit, 0) - IFNULL(jl.debit, 0)
                 WHEN a.type = 'Expense' THEN IFNULL(jl.debit, 0) - IFNULL(jl.credit, 0)
                 ELSE 0 END) AS net_income
    FROM journal_lines jl
    JOIN journal_entries je ON je.id = jl.entry_id
    JOIN accounts a ON a.id = jl.account_id
    WHERE je.entry_date <= ? AND je.client_id = ? AND a.type IN ('Revenue', 'Expense')
    ";
    $stmt = $conn->prepare($ni_sql);
    $stmt->bind_param('si', $end_date, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $net_income = floatval($row['net_income']);
    }

    // --- 4. Get Withdrawals
    $withdrawal_sql = "
    SELECT SUM(r.amount) AS total
    FROM receipts r
    JOIN categories c ON c.name = r.category AND c.client_id = r.client_id
    WHERE r.client_id = ? AND r.receipt_date <= ? AND c.type = 'withdrawal'
    ";
    $stmt = $conn->prepare($withdrawal_sql);
    $stmt->bind_param('is', $client_id, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $withdrawals = floatval($row['total']);
    }

    // --- 5. Compute Total Equity
    $total_equity = $beginning_capital + $net_income - $withdrawals;

    $accounts_data['Equity'][] = ['account' => 'Beginning Capital', 'amount' => $beginning_capital];
    $accounts_data['Equity'][] = ['account' => 'Net Income', 'amount' => $net_income];
    $accounts_data['Equity'][] = ['account' => 'Withdrawals', 'amount' => -$withdrawals];
    $accounts_data['Equity'][] = ['account' => 'Total Equity', 'amount' => $total_equity];

    return $accounts_data;
}
