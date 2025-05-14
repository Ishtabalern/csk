<?php
include '../includes/db.php';

$id = $_POST['id'];
$vendor = $_POST['vendor'];
$category = $_POST['category'];
$amount = $_POST['amount'];
$payment_method = $_POST['payment_method'];
$date = $_POST['date'];

$stmt = $conn->prepare("UPDATE scanned_receipts SET vendor=?, category=?, amount=?, payment_method=?, receipt_date=? WHERE id=?");
$stmt->bind_param("ssdssi", $vendor, $category, $amount, $payment_method, $date, $id);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "error";
}
?>
