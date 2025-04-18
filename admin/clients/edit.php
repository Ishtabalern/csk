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

<h2>Edit Client</h2>

<?php if ($success): ?>
    <p style="color: green;"><?= $success ?></p>
<?php elseif ($error): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST">
    <label>Client Name*</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($client['name']) ?>" required><br><br>

    <label>Contact Person</label><br>
    <input type="text" name="contact_person" value="<?= htmlspecialchars($client['contact_person']) ?>"><br><br>

    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($client['email']) ?>"><br><br>

    <label>Phone</label><br>
    <input type="text" name="phone" value="<?= htmlspecialchars($client['phone']) ?>"><br><br>

    <label>Address</label><br>
    <textarea name="address"><?= htmlspecialchars($client['address']) ?></textarea><br><br>

    <button type="submit">💾 Save Changes</button>
</form>

<br>
<a href="list.php">← Back to Client List</a>
