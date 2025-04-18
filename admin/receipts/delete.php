<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../../includes/db.php';

// Check if receipt ID is passed
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Receipt ID missing.");
}

$id = intval($_GET['id']);

// Get the image path before deleting
$stmt = $conn->prepare("SELECT image_path FROM receipts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$receipt = $result->fetch_assoc();

if ($receipt) {
    // Delete the receipt
    $stmt = $conn->prepare("DELETE FROM receipts WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Delete the image file
        if (file_exists($receipt['image_path'])) {
            unlink($receipt['image_path']);
        }
        header("Location: list.php?msg=Receipt deleted successfully.");
        exit;
    } else {
        die("Error deleting receipt: " . $conn->error);
    }
} else {
    die("Receipt not found.");
}
