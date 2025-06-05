<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db.php';

$id = $_POST['id'];
$vendor = $_POST['vendor'];
$category = $_POST['category'];
$amount = $_POST['amount'];
$payment_method = $_POST['payment_method'];
$date = $_POST['date'];

// Set confidence score and quality flag after manual edit
$score = 100;
$quality = 'Excellent - Edited';

$stmt = $conn->prepare("UPDATE scanned_receipts SET vendor=?, category=?, amount=?, payment_method=?, receipt_date=?, confidence_score = ?, quality_flag = ? WHERE id=?");
$stmt->bind_param("ssdssfsi", $vendor, $category, $amount, $payment_method, $date, $score, $quality, $id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "score" => $score,
        "quality" => $quality
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error // Capture MySQL error message
    ]);
}

?>
