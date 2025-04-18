<?php
session_start();
if ($_SESSION['role'] !== 'employee') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';

// Fetch clients for dropdown
$clients = $conn->query("SELECT id, name FROM clients");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $receipt_date = $_POST['receipt_date'];
    $vendor = $_POST['vendor'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $uploaded_by = $_SESSION['user_id'];

    // Handle image upload
    $target_dir = "../uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $image_name = basename($_FILES["image"]["name"]);
    $image_path = $target_dir . time() . "_" . $image_name;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $image_path)) {
        $stmt = $conn->prepare("INSERT INTO receipts (client_id, receipt_date, vendor, category, amount, payment_method, uploaded_by, image_path, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issssdss", $client_id, $receipt_date, $vendor, $category, $amount, $payment_method, $uploaded_by, $image_path);

        if ($stmt->execute()) {
            $success = "Receipt uploaded successfully.";
        } else {
            $error = "Database error: " . $stmt->error;
        }
    } else {
        $error = "Failed to upload image.";
    }
}
?>

<h2>Upload Receipt</h2>

<?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST" enctype="multipart/form-data">
    <label>Client:</label><br>
    <select name="client_id" required>
        <option value="">Select Client</option>
        <?php while ($row = $clients->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Receipt Date:</label><br>
    <input type="date" name="receipt_date" required><br><br>

    <label>Vendor:</label><br>
    <input type="text" name="vendor" required><br><br>

    <label>Category:</label><br>
    <input type="text" name="category" required><br><br>

    <label>Amount:</label><br>
    <input type="number" step="0.01" name="amount" required><br><br>

    <label>Payment Method:</label><br>
    <select name="payment_method" required>
        <option value="">Select Method</option>
        <option value="Cash">Cash</option>
        <option value="Bank Transfer">Bank Transfer</option>
        <option value="GCash">GCash</option>
    </select><br><br>

    <label>Receipt Image:</label><br>
    <input type="file" name="image" accept="image/*" required><br><br>

    <button type="submit">Upload</button>
</form>

<a href="../employee_dashboard.php">← Back to Dashboard</a>
