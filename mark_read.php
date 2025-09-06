<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) exit();

$user_id = $_SESSION['user_id'];
$sender_id = $_GET['receiver_id']; // Actually the other user

$sql = "UPDATE messages SET status = 'read' WHERE sender_id = ? AND receiver_id = ? AND status != 'read'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $sender_id, $user_id);
$stmt->execute();
