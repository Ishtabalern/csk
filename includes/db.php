<?php
$host = "localhost";       // or 127.0.0.1
$dbname = "csk"; // change this to your actual DB name
$username = "admin";        // or whatever DB username you're using
$password = "123";            // your DB password (if any)

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
