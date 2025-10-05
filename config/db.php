<?php
$host = "localhost";
$user = "root";      // default user in XAMPP
$pass = "";          // leave blank (default)
$db   = "user_db";   // your database name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
