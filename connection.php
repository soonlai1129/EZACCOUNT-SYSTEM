<?php
// Pick the correct timezone for the user/business
date_default_timezone_set('Asia/Kuala_Lumpur');

$servername = "localhost"; // database server name
$username = "root"; // database username
$password = "";  // password for database connection
$database = "ezaccount"; // database name that wanted to connect

// Create database connection syntax then assign it to $conn variable
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>