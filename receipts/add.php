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
    $category_name = trim($_POST['category']);
    $amount = (float)$_POST['amount'];
    $payment_method = $_POST['payment_method']; 
    $selected_credit_account_id = (int)$_POST['credit_account_id'];
    $uploaded_by = $_SESSION['user_id'];

    // Handle image upload
    $target_dir = "../uploads/receipts/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $image_name = basename($_FILES["image"]["name"]);
    $image_path = $target_dir . time() . "_" . $image_name;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $image_path)) {
        // Insert into receipts table
        $stmt = $conn->prepare("INSERT INTO receipts (client_id, receipt_date, vendor, category, amount, payment_method, uploaded_by, image_path, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssdsss", $client_id, $receipt_date, $vendor, $category_name, $amount, $payment_method, $uploaded_by, $image_path);

        if ($stmt->execute()) {
            $success = "Receipt uploaded successfully.";

            // Fetch or create category
            $category_stmt = $conn->prepare("SELECT * FROM categories WHERE name = ? AND client_id = ?");
            $category_stmt->bind_param("si", $category_name, $client_id);
            $category_stmt->execute();
            $category_result = $category_stmt->get_result();
            $category_data = $category_result->fetch_assoc();

            if (!$category_data) {
                // Determine type
                $category_words = explode(' ', strtolower($category_name));
                $category_type = 'expense'; // default
                $debit_account_id = 4;  // Default: Utilities Expense
                $credit_account_id = $selected_credit_account_id; // Use selected

                if (in_array('income', $category_words) || 
                    (isset($category_words[1]) && $category_words[1] === 'income') || 
                    stripos($category_name, 'sale') !== false || 
                    stripos($category_name, 'revenue') !== false) {

                    $category_type = 'income';
                    $debit_account_id = 1;  // Cash
                    $credit_account_id = $selected_credit_account_id;

                } elseif (stripos($category_name, 'withdraw') !== false) {
                    $category_type = 'withdrawal';
                    $debit_account_id = 5;  // Owner's Withdrawals
                    $credit_account_id = $selected_credit_account_id;
                }



                $insert_cat = $conn->prepare("INSERT INTO categories (client_id, name, type, debit_account_id, credit_account_id, created_at)
                                              VALUES (?, ?, ?, ?, ?, NOW())");
                $insert_cat->bind_param("issii", $client_id, $category_name, $category_type, $debit_account_id, $credit_account_id);
                $insert_cat->execute();

                // Use inserted values
                $category_data = [
                    'type' => $category_type,
                    'debit_account_id' => $debit_account_id,
                    'credit_account_id' => $credit_account_id
                ];
            }

            // Insert into journal_entries
            $desc = "Auto entry for receipt - " . $vendor;
            $journal_stmt = $conn->prepare("INSERT INTO journal_entries (client_id, entry_date, description, created_at)
                                            VALUES (?, ?, ?, NOW())");
            $journal_stmt->bind_param("iss", $client_id, $receipt_date, $desc);
            $journal_stmt->execute();
            $entry_id = $journal_stmt->insert_id;

            // Insert journal_lines (debit)
            $line_stmt = $conn->prepare("INSERT INTO journal_lines (entry_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");
            $zero = 0.00;
            $line_stmt->bind_param("iidd", $entry_id, $category_data['debit_account_id'], $amount, $zero);
            $line_stmt->execute();

            // Insert journal_lines (credit)
            $line_stmt->bind_param("iidd", $entry_id, $category_data['credit_account_id'], $zero, $amount);
            $line_stmt->execute();

        } else {
            $error = "Database error: " . $stmt->error;
        }

    } else {
        $error = "Failed to upload image.";
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Receipt</title>
    <link rel="stylesheet" href="add.css">
</head>
<body>

    <h1>Upload Receipt</h1>
    <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <div class="forms-container"> 
        <form method="POST" enctype="multipart/form-data">

            <div class="input full-width">
                <label>Client:</label>
                <select name="client_id" required>
                    <option value="">Select Client</option>
                    <?php while ($row = $clients->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="input">
                <label>Receipt Date:</label>
                <input type="date" name="receipt_date" required>
            </div>

            <div class="input">
                <label>Vendor:</label>
                <input type="text" name="vendor" required>
            </div>

            <div class="input">
                <label>Category:</label>
                <input type="text" name="category" required>
            </div>

            <div class="input">
                <label>Amount:</label>
                <input type="number" step="0.01" name="amount" required>
            </div>

            <div class="input">
                <label>Payment Method:</label>
                <select name="payment_method" required>
                    <option value="">Select Method</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="GCash">GCash</option>
                </select>
            </div>

            <div class="input">
                <label>Credit Account:</label>
                <select name="credit_account_id" required>
                    <option value="">Select Credit Account</option>
                    <?php
                    $accounts = $conn->query("SELECT id, name FROM accounts WHERE type IN ('Liability', 'Equity', 'Revenue', 'Asset')");
                    while ($acc = $accounts->fetch_assoc()):
                    ?>
                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>


            <div class="input full-width">
                <label>Receipt Image:</label>
                <input type="file" name="image" accept="image/*" required>
            </div>

            <button type="submit">Upload</button>
        </form>

        <a href="../employee_dashboard.php">← Back to Dashboard</a>
    </div>
    
</body>
</html>






