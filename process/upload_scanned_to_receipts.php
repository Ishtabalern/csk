<?php
session_start();
if ($_SESSION['role'] !== 'employee') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id'])) {
    $client_id = (int)$_POST['client_id'];
    $uploaded_by = $_SESSION['user_id'];

    // Fetch all scanned receipts
    $scanned = $conn->query("SELECT * FROM scanned_receipts");

    while ($row = $scanned->fetch_assoc()) {
        $vendor = $row['vendor'];
        $category = trim($row['category']);
        $amount = (float)$row['amount'];
        $payment_method = $row['payment_method'] ?? 'Cash';
        $receipt_date = $row['receipt_date'];
        $image_path = $row['image_path'];

        // Determine account based on category
        $category_type = 'expense';
        $debit_account_id = 4;
        $credit_account_id = 1;

        if (stripos($category, 'sale') !== false || stripos($category, 'income') !== false || stripos($category, 'revenue') !== false) {
            $category_type = 'income';
            $debit_account_id = 1;
            $credit_account_id = 3;
        } elseif (stripos($category, 'withdraw') !== false) {
            $category_type = 'withdrawal';
            $debit_account_id = 5;
            $credit_account_id = 2;
        }

        // Insert or find category
        $cat_stmt = $conn->prepare("SELECT * FROM categories WHERE name = ? AND client_id = ?");
        $cat_stmt->bind_param("si", $category, $client_id);
        $cat_stmt->execute();
        $cat_res = $cat_stmt->get_result();

        if ($cat_res->num_rows === 0) {
            $insert_cat = $conn->prepare("INSERT INTO categories (client_id, name, type, debit_account_id, credit_account_id, created_at)
                                          VALUES (?, ?, ?, ?, ?, NOW())");
            $insert_cat->bind_param("issii", $client_id, $category, $category_type, $debit_account_id, $credit_account_id);
            $insert_cat->execute();
        } else {
            $existing = $cat_res->fetch_assoc();
            $debit_account_id = $existing['debit_account_id'];
            $credit_account_id = $existing['credit_account_id'];
        }

        // Insert into receipts
        $stmt = $conn->prepare("INSERT INTO receipts (client_id, receipt_date, vendor, category, amount, payment_method, uploaded_by, image_path, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssdsss", $client_id, $receipt_date, $vendor, $category, $amount, $payment_method, $uploaded_by, $image_path);
        $stmt->execute();

        // Insert into journal_entries
        $desc = "Auto-uploaded scanned receipt - " . $vendor;
        $journal_stmt = $conn->prepare("INSERT INTO journal_entries (client_id, entry_date, description, created_at)
                                        VALUES (?, ?, ?, NOW())");
        $journal_stmt->bind_param("iss", $client_id, $receipt_date, $desc);
        $journal_stmt->execute();
        $entry_id = $journal_stmt->insert_id;

        // Insert journal_lines
        $line_stmt = $conn->prepare("INSERT INTO journal_lines (entry_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");
        $zero = 0.00;
        $line_stmt->bind_param("iidd", $entry_id, $debit_account_id, $amount, $zero);
        $line_stmt->execute();

        $line_stmt->bind_param("iidd", $entry_id, $credit_account_id, $zero, $amount);
        $line_stmt->execute();
    }

    header("Location: ../receipts/scan.php?success=Receipts uploaded.");
    exit;
} else {
    header("Location: ../receipts/scan.php?error=Missing client.");
    exit;
}
