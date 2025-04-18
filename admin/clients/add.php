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

<h2>Add New Client</h2>

<?php if ($success): ?>
    <p style="color: green;"><?= $success ?></p>
<?php elseif ($error): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST">
    <label>Client Name*</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required><br><br>

    <label>Contact Person</label><br>
    <input type="text" name="contact_person" value="<?= htmlspecialchars($contact_person) ?>"><br><br>

    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>"><br><br>

    <label>Phone</label><br>
    <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>"><br><br>

    <label>Address</label><br>
    <textarea name="address"><?= htmlspecialchars($address) ?></textarea><br><br>

    <button type="submit">➕ Add Client</button>
</form>

<br>
<a href="list.php">← Back to Client List</a>
<a href="../../admin_dashboard.php">← Back to Admin Dashboard</a>