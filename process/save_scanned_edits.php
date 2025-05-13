<?php
require_once '../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$receipts = $data['receipts'];

foreach ($receipts as $receipt) {
    $stmt = $conn->prepare("UPDATE scanned_receipts SET receipt_date = ?, vendor = ?, category = ?, amount = ? WHERE id = ?");
    $stmt->bind_param("sssdi", $receipt['receipt_date'], $receipt['vendor'], $receipt['category'], $receipt['amount'], $receipt['id']);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true]);
?>
