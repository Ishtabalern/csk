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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../partials/topbar.css">
    <style>
            * {
            margin: 0;
            padding: 0;
            list-style: none;
            text-decoration: none;
            box-sizing: border-box;
            scroll-behavior: smooth;
            font-family: Arial, sans-serif;
        }

        h1{
            color: #1ABC9C;
            padding: 20px;
        }

        .forms-container {
            display: flex;
            justify-content: center;
            flex-direction: column;
            margin:40px auto;
        }

        .forms-container form {
            
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            max-width: 600px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            background-color: #f9f9f9;
            margin: auto;
        }

        form .input {
            flex: 1 1 calc(50% - 10px);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .input.full-width {
            flex: 1 1 100%;
        }

        .input label {
            font-weight: bold;
            color: #5d5d5d;
            margin-bottom: 5px;
        }

        .input input[type="text"],
        .input input[type="email"],
        .input input[type="number"],
        .input input[type="date"],
        .input input[type="file"],
        .input select,
        .input textarea {
            width: 100%;
            border-radius: 4px;
            padding: 12px 10px;
            border: 1px solid #B1B1B1;
            outline: none;
            font-size: 14px;
        }


        button[type="submit"] {
            flex: 1 1 100%;
            margin-top: 10px;
            padding: 12px;
            background-color: #fff;
            color: #616161;
            font-size: 16px;
            border: 1px solid #1ABC9C;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #1ABC9C;
            color: #fff;
        }

        
        .btns{
            display: flex;
            align-items: center;
            justify-content: space-evenly;
            width: 100%;
            max-width: 700px;
            margin:auto;
            flex-wrap:wrap;
        }
        
        a {
            color: #1ABC9C;
            text-decoration: none;
            font-weight: bold;
            margin: 20px auto;
        }

        a:hover {
            text-decoration: underline;
        }
 
    </style>
</head>
<body>

    <div class="topbar-container">
        <div class="header">
            <img src="../../imgs/csk_logo.png" alt="">
            <h1 style="color: #0B440F">Add New Receipt</h1>
        </div>
       
        <div class="btn">
            <a href="list.php">← Back to Receipt List</a>
            <a href="../../admin_dashboard.php">← Back to Admin Dashboard</a>
        </div>
    </div>


    <?php if ($success): ?>
        <p style="color: green;"><?= $success ?></p>
    <?php elseif ($error): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

    <div class="forms-container">
        <form method="POST" enctype="multipart/form-data">
            
            <div class="input full-width">
                <label>Client*</label>
                <select name="client_id" required>
                    <option value="">Select Client</option>
                    <?php while ($row = $clients->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="input">
                <label>Receipt Date*</label>
                <input type="date" name="receipt_date" required>
            </div>

            <div class="input">
                <label>Vendor</label>
                <input type="text" name="vendor">
            </div>

            <div class="input">
                <label>Category</label>
                <input type="text" name="category">
            </div>

            <div class="input">
                <label>Amount*</label>
                <input type="number" name="amount" step="0.01" required>
            </div>

            <div class="input">
                <label>Payment Method</label>
                <input type="text" name="payment_method">
            </div>

            <div class="input">
                <label>Receipt Image*</label>
                <input type="file" name="receipt_image" accept="image/*" required>
            </div>

            <button type="submit">💾 Add Receipt</button>
        </form>
        
    </div>

</body>
</html>

