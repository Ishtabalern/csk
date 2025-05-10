<?php
require '../vendor/autoload.php';
include '../includes/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

$client_id = $_POST['client_id'];
$end_date = $_POST['end_date'];

// (Reuse your existing balance sheet SQL/data logic here)
// For brevity, I’ll assume you wrap your existing logic in a function:
include 'generate_balance_data.php'; // This should populate: $accounts_data, $totals, $beginning_capital, $net_income, $withdrawals, $total_equity

if (isset($_POST['export_excel'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $row = 1;

    $sheet->setCellValue("A$row", "Balance Sheet as of $end_date"); $row += 2;

    foreach (['assets', 'liabilities'] as $section) {
        $sheet->setCellValue("A$row", ucfirst($section)); $row++;
        $sheet->setCellValue("A$row", "Account");
        $sheet->setCellValue("B$row", "Amount");
        $row++;
        foreach ($accounts_data[$section] ?? [] as $acc) {
            $sheet->setCellValue("A$row", $acc['name']);
            $sheet->setCellValue("B$row", $acc['balance']);
            $row++;
        }
        $sheet->setCellValue("A$row", "Total " . ucfirst($section));
        $sheet->setCellValue("B$row", $totals[$section]);
        $row += 2;
    }

    // Equity
    $sheet->setCellValue("A$row", "Equity"); $row++;
    $sheet->setCellValue("A$row", "Component");
    $sheet->setCellValue("B$row", "Amount"); $row++;
    $sheet->setCellValue("A$row", "Beginning Capital");
    $sheet->setCellValue("B$row", $beginning_capital); $row++;
    $sheet->setCellValue("A$row", "Net Income");
    $sheet->setCellValue("B$row", $net_income); $row++;
    $sheet->setCellValue("A$row", "Withdrawals");
    $sheet->setCellValue("B$row", -$withdrawals); $row++;
    $sheet->setCellValue("A$row", "Total Equity");
    $sheet->setCellValue("B$row", $total_equity); $row += 2;

    $sheet->setCellValue("A$row", "Total Liabilities & Equity");
    $sheet->setCellValue("B$row", $totals['liabilities'] + $total_equity);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"Balance_Sheet_$end_date.xlsx\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}

if (isset($_POST['export_pdf'])) {
    ob_start();
    ?>

    <h2 style="text-align:center;">Balance Sheet as of <?= htmlspecialchars($end_date) ?></h2>
    <h3>Assets</h3>
    <table border="1" cellpadding="5">
        <tr><th>Account</th><th>Amount</th></tr>
        <?php foreach ($accounts_data['assets'] ?? [] as $acc): ?>
            <tr><td><?= htmlspecialchars($acc['name']) ?></td><td><?= number_format($acc['balance'], 2) ?></td></tr>
        <?php endforeach; ?>
        <tr><td><strong>Total Assets</strong></td><td><strong><?= number_format($totals['assets'], 2) ?></strong></td></tr>
    </table>

    <h3>Liabilities</h3>
    <table border="1" cellpadding="5">
        <tr><th>Account</th><th>Amount</th></tr>
        <?php foreach ($accounts_data['liabilities'] ?? [] as $acc): ?>
            <tr><td><?= htmlspecialchars($acc['name']) ?></td><td><?= number_format($acc['balance'], 2) ?></td></tr>
        <?php endforeach; ?>
        <tr><td><strong>Total Liabilities</strong></td><td><strong><?= number_format($totals['liabilities'], 2) ?></strong></td></tr>
    </table>

    <h3>Equity</h3>
    <table border="1" cellpadding="5">
        <tr><td>Beginning Capital</td><td><?= number_format($beginning_capital, 2) ?></td></tr>
        <tr><td>Net Income</td><td><?= number_format($net_income, 2) ?></td></tr>
        <tr><td>Withdrawals</td><td>(<?= number_format($withdrawals, 2) ?>)</td></tr>
        <tr><td><strong>Total Equity</strong></td><td><strong><?= number_format($total_equity, 2) ?></strong></td></tr>
    </table>

    <h3>Total Liabilities & Equity</h3>
    <table border="1" cellpadding="5">
        <tr><td><strong>Total</strong></td><td><strong><?= number_format($totals['liabilities'] + $total_equity, 2) ?></strong></td></tr>
    </table>

    <?php
    $html = ob_get_clean();

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4');
    $dompdf->render();

    $dompdf->stream("Balance_Sheet_$end_date.pdf", ["Attachment" => true]);
    exit;
}
