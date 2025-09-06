<?php
$servername = "localhost"; // Assuming localhost, change if needed
$username = "uxhc7qjwxxfub";
$password = "g4t0vezqttq6";
$dbname = "db3ktceshcsusm";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
