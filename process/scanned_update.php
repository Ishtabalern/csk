<?php
session_start();
if ($_SESSION['role'] !== 'employee') {
    http_response_code(403);
    echo "Unauthorized.";
    exit;
}

include '../includes/db.php';

// Get POST data safely
$id = (int) $_POST['id'];
$vendor = trim($_POST['vendor']);
$category = trim($_POST['category']);
$amount = floatval($_POST['amount']);
$payment_method = trim($_POST['payment_method']);
$date = $_POST['date']; // Assuming YYYY-MM-DD format

// Validate
if (!$id || !$vendor || !$category || !$amount || !$payment_method || !$date) {
    http_response_code(400);
    echo "Missing fields.";
    exit;
}

// Set the quality flag
$quality_flag = "Excellent - Edited";

// Prepare and bind
$stmt = $conn->prepare("
    UPDATE scanned_receipts 
    SET vendor = ?, category = ?, amount = ?, payment_method = ?, receipt_date = ?, quality_flag = ? 
    WHERE id = ?
");

if (!$stmt) {
    http_response_code(500);
    echo "Prepare failed: " . $conn->error;
    exit;
}

$stmt->bind_param("ssdsssi", $vendor, $category, $amount, $payment_method, $date, $quality_flag, $id);

// Execute and respond
if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'score' => '',  // Optional if you don't use it
        'quality' => $quality_flag
    ]);
} else {
    http_response_code(500);
    echo "Database error: " . $stmt->error;
}
?>
