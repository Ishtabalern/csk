<?php
require '../vendor/autoload.php';
require 'generate_balance_data.php'; // This pulls in real balance data logic

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ob_get_length()) ob_end_clean();

    // Get filters from POST
    $clientId = $_POST['client_id'] ?? null;
    $year = $_POST['year'] ?? null;

    // Generate data using your actual function
    $data = generateBalanceSheetData($conn, $clientId, $year);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Balance Sheet");

    $row = 1;
    $sheet->setCellValue("A$row", "Balance Sheet");
    $sheet->mergeCells("A$row:B$row");
    $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row += 2;

    foreach ($data as $section => $items) {
        if (empty($items)) continue;

        $sheet->setCellValue("A$row", $section);
        $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(14);
        $row++;

        $sheet->setCellValue("A$row", "Account");
        $sheet->setCellValue("B$row", "Amount");
        $sheet->getStyle("A$row:B$row")->getFont()->setBold(true);
        $row++;

        $sectionTotal = 0;
        foreach ($items as $item) {
            $sheet->setCellValue("A$row", $item['account']);
            $sheet->setCellValue("B$row", $item['amount']);
            $sectionTotal += $item['amount'];
            $row++;
        }

        $sheet->setCellValue("A$row", "Total $section");
        $sheet->setCellValue("B$row", $sectionTotal);
        $sheet->getStyle("A$row:B$row")->getFont()->setBold(true);
        $row += 2;
    }

    // Apply formatting
    $lastRow = $sheet->getHighestRow();
    $sheet->getStyle("A4:B$lastRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("B4:B$lastRow")->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getColumnDimension('A')->setWidth(30);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getStyle("A4:B$lastRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Balance_Sheet.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
