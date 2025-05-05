<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../../includes/db.php';

$success = $error = "";

// Check if client ID is passed
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Client ID missing.");
}

$id = intval($_GET['id']);

// Fetch existing client data
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

if (!$client) {
    die("Client not found.");
}

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if ($name == "") {
        $error = "Client name is required.";
    } else {
        $update = $conn->prepare("UPDATE clients SET name = ?, contact_person = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $update->bind_param("sssssi", $name, $contact_person, $email, $phone, $address, $id);

        if ($update->execute()) {
            $success = "Client updated successfully!";
            // Refresh client data
            $client = [
                'name' => $name,
                'contact_person' => $contact_person,
                'email' => $email,
                'phone' => $phone,
                'address' => $address
            ];
        } else {
            $error = "Error updating client: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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

        body {   
        background-color: #dce3e9;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        }

        .container {
        max-width: 740px;
        width: 90%;
        margin: auto;
        border-radius: 5px;
        padding: 30px;
        min-height: 100px;
        text-align: center; /* center text inside container */
        background-color: #fff;
        }

        .container h2 {
        color: #1ABC9C;
        margin-bottom: 20px;
        }

        .container .forms-container {
        display: flex;
        justify-content: center;
        }

        .forms-container form {
        margin-top: 30px;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
        }

        form .input {
        flex: 1 1 calc(50% - 10px);
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        }

        .input label {
        font-weight: bold;
        color: #5d5d5d;
        }

        .input input[type="text"],
        .input input[type="email"],
        .input textarea {
        width: 100%;
        border-radius: 4px;
        padding: 15px 8px;
        border: 1px solid #B1B1B1;
        outline: none;
        font-size: 14px;
        }

        .input textarea {
        height: 100px;
        resize: vertical;
        }

        .input.full-width {
        flex: 1 1 100%;
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



        /* Responsive: Stack inputs if screen is small */
        @media (max-width: 600px) {
        form .input {
            flex: 1 1 100%;
            align-items: center;
        }
        }


         a{
        padding: 12px;
        background-color: #fff;
        color: #616161;
        font-size: 16px;
        border: 1px solid #1ABC9C;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease, color 0.3s ease;
        display:block;
        }

         a:hover {
        background-color: #1ABC9C;
        color: #fff;
        }
    </style>
</head>
<body>
    
    <div class="container">
        <h2>Edit Client</h2>

        <?php if ($success): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php elseif ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>

        <div class="forms-container">
            <form method="POST">
                <div class="input">
                    <label>Client Name*</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($client['name']) ?>" required>
                </div>

                <div class="input">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person" value="<?= htmlspecialchars($client['contact_person']) ?>">
                </div>

                <div class="input">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($client['email']) ?>">
                </div>

                <div class="input">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($client['phone']) ?>">
                </div>

                <div class="input full-width">
                    <label>Address</label>
                    <textarea name="address"><?= htmlspecialchars($client['address']) ?></textarea>
                </div>


                <button type="submit">💾 Save Changes</button>
            </form>
        </div>

        <br>
        <div class="btn">
            <a href="list.php">← Back to Client List</a>
        </div>
        
    </div>

</body>
</html>
