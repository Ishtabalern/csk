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

    // --- Auto-create category if not existing ---
    $category_name = trim($category);
    $check_cat = $conn->prepare("SELECT id FROM categories WHERE client_id = ? AND name = ?");
    $check_cat->bind_param("is", $client_id, $category_name);
    $check_cat->execute();
    $check_cat->store_result();

    if ($check_cat->num_rows === 0) {
        // Determine type based on name
        $category_type = (stripos($category_name, 'sale') !== false || stripos($category_name, 'revenue') !== false) ? 'income' : 'expense';

        // Default account IDs
        $default_debit_account_id = 2;  // Expense account
        $default_credit_account_id = 1; // Cash account

        $insert_cat = $conn->prepare("INSERT INTO categories (client_id, name, type, debit_account_id, credit_account_id, created_at)
                                    VALUES (?, ?, ?, ?, ?, NOW())");
        $insert_cat->bind_param("issii", $client_id, $category_name, $category_type, $default_debit_account_id, $default_credit_account_id);
        $insert_cat->execute();
    }


    // Handle image upload
    $target_dir = "../uploads/receipts/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $image_name = basename($_FILES["image"]["name"]);
    $image_path = $target_dir . time() . "_" . $image_name;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $image_path)) {
        $stmt = $conn->prepare("INSERT INTO receipts (client_id, receipt_date, vendor, category, amount, payment_method, uploaded_by, image_path, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssssss", $client_id, $receipt_date, $vendor, $category, $amount, $payment_method, $uploaded_by, $image_path);
    
        if ($stmt->execute()) {
            $success = "Receipt uploaded successfully.";
        
            // STEP 1: Fetch category info to get type, debit_account_id, credit_account_id
            $category_stmt = $conn->prepare("SELECT * FROM categories WHERE name = ? AND client_id = ?");
            $category_stmt->bind_param("si", $category, $client_id);
            $category_stmt->execute();
            $category_result = $category_stmt->get_result();
            $category_data = $category_result->fetch_assoc();
        
            if ($category_data) {
                $category_type = $category_data['type'];
                $debit_account_id = $category_data['debit_account_id'];
                $credit_account_id = $category_data['credit_account_id'];
        
                // STEP 2: Insert journal entry
                $journal_stmt = $conn->prepare("INSERT INTO journal_entries (client_id, entry_date, description, created_at) VALUES (?, ?, ?, NOW())");
                $desc = "Auto entry for receipt - " . $vendor;
                $journal_stmt->bind_param("iss", $client_id, $receipt_date, $desc); 
                $journal_stmt->execute();
                $entry_id = $journal_stmt->insert_id;
        
                // STEP 3: Insert debit line
                $line_stmt = $conn->prepare("INSERT INTO journal_lines (entry_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");
                $zero = 0.00;
                $line_stmt->bind_param("iidd", $entry_id, $debit_account_id, $amount, $zero);
                $line_stmt->execute();
        
                // STEP 4: Insert credit line
                $line_stmt->bind_param("iidd", $entry_id, $credit_account_id, $zero, $amount);
                $line_stmt->execute();
            }
        
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
