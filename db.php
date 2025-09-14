<?php
$host = "localhost";
$user = "root";
$pass = ""; // your MySQL root password (default is empty on XAMPP)
$db   = "password_vault";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
