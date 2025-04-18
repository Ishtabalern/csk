<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../../includes/db.php';

// Check if client ID is passed
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Client ID missing.");
}

$id = intval($_GET['id']);

// Delete client
$stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: list.php?msg=Client deleted successfully.");
    exit;
} else {
    die("Error deleting client: " . $conn->error);
}
