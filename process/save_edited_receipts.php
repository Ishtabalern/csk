<?php
require_once '../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$client_id = intval($data['client_id']);
$receipts = $data['receipts'];

foreach ($receipts as $receipt) {
    $stmt = $conn->prepare("INSERT INTO receipts (client_id, receipt_date, vendor, category, amount, img_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssds", $client_id, $receipt['receipt_date'], $receipt['vendor'], $receipt['category'], $receipt['amount'], $receipt['image_path']);
    $stmt->execute();
    $stmt->close();

    // Delete from scanned_receipts
    $deleteStmt = $conn->prepare("DELETE FROM scanned_receipts WHERE id = ?");
    $deleteStmt->bind_param("i", $receipt['id']);
    $deleteStmt->execute();
    $deleteStmt->close();
}

echo json_encode(['success' => true]);
?>
