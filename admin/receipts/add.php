<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../../includes/db.php';

// Fetch clients for dropdown
$clients = $conn->query("SELECT id, name FROM clients");

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = intval($_POST['client_id']);
    $receipt_date = $_POST['receipt_date'];
    $vendor = $_POST['vendor'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $uploaded_by = $_SESSION['user_id'];  // Assuming the admin is logged in as 'user_id'
    
    // Handle file upload
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['receipt_image']['tmp_name'];
        $file_name = $_FILES['receipt_image']['name'];
        $upload_dir = "../../uploads/receipts/";
        $file_path = $upload_dir . basename($file_name);
        
        if (move_uploaded_file($file_tmp, $file_path)) {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO receipts (client_id, receipt_date, vendor, category, amount, payment_method, uploaded_by, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $client_id, $receipt_date, $vendor, $category, $amount, $payment_method, $uploaded_by, $file_path);

            if ($stmt->execute()) {
                $success = "Receipt added successfully!";
            } else {
                $error = "Error adding receipt: " . $conn->error;
            }
        } else {
            $error = "Error uploading receipt image.";
        }
    } else {
        $error = "Please upload a valid receipt image.";
    }
}
?>

<h2>Add New Receipt</h2>

<?php if ($success): ?>
    <p style="color: green;"><?= $success ?></p>
<?php elseif ($error): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <label>Client*</label><br>
    <select name="client_id" required>
        <option value="">Select Client</option>
        <?php while ($row = $clients->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Receipt Date*</label><br>
    <input type="date" name="receipt_date" required><br><br>

    <label>Vendor</label><br>
    <input type="text" name="vendor"><br><br>

    <label>Category</label><br>
    <input type="text" name="category"><br><br>

    <label>Amount*</label><br>
    <input type="number" name="amount" step="0.01" required><br><br>

    <label>Payment Method</label><br>
    <input type="text" name="payment_method"><br><br>

    <label>Receipt Image*</label><br>
    <input type="file" name="receipt_image" accept="image/*" required><br><br>

    <button type="submit">💾 Add Receipt</button>
</form>

<br>
<a href="list.php">← Back to Receipt List</a>
<a href="../../admin_dashboard.php">← Back to Admin Dashboard</a>