<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) exit();

$user_id = $_SESSION['user_id'];
$receiver_id = $_GET['receiver_id'];

$sql = "SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY timestamp ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

// Update status to delivered for received messages
$update_sql = "UPDATE messages SET status = 'delivered' WHERE sender_id = ? AND receiver_id = ? AND status = 'sent'";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $receiver_id, $user_id);
$update_stmt->execute();

echo json_encode($messages);
