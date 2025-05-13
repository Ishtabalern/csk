<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../../includes/db.php';

$name = $contact_person = $email = $phone = $address = "";
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if ($name == "") {
        $error = "Client name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO clients (name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $contact_person, $email, $phone, $address);
        if ($stmt->execute()) {
            $success = "Client added successfully!";
            $name = $contact_person = $email = $phone = $address = "";
        } else {
            $error = "Error adding client: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add New Client</title>
    <link rel="stylesheet" href="../../styles/admin_clients/add.css">
    <link rel="stylesheet" href="../../partials/topbar.css">
</head>
<body>

    <div class="topbar-container">
        <div class="header">
            <img src="../../imgs/csk_logo.png" alt="">
            <h1 style="color:#1ABC9C">Add New Client</h1>
        </div>
       
        <div class="btn">
            <a href="list.php">← Back to Client List</a>
            <a href="../../admin_dashboard.php">← Back to Admin Dashboard</a>
        </div>
    </div>

    <div class="container">
        <h2>Enter New Client</h2>
       
        <?php if ($success): ?>
        <p style="color: green;"><?= $success ?></p>
        <?php elseif ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>

        <div class="forms-container">
            <form method="POST">
                <div class="input">
                    <label>Client Name*</label><br>
                    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
                </div>
                
                <div class="input">
                    <label>Contact Person</label><br>
                    <input type="text" name="contact_person" value="<?= htmlspecialchars($contact_person) ?>">
                </div>

                <div class="input">
                    <label>Email</label><br>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>">
                </div>

                <div class="input">
                    <label>Phone</label><br>
                    <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>">
                </div>

                <div class="input full-width">
                    <label>Address</label><br>
                    <textarea name="address"><?= htmlspecialchars($address) ?></textarea>
                </div>

                
                <button type="submit">➕ Add Client</button>
            </form>
        </div>

    
       
    </div>



</body>
</html>
