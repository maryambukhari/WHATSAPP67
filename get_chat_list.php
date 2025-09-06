<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) exit();

$user_id = $_SESSION['user_id'];

// Get unique conversations with last message
$sql = "SELECT u.id, u.username, m.message AS last_message 
        FROM users u 
        INNER JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id) 
        WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ? 
        GROUP BY u.id 
        ORDER BY m.timestamp DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$chats = [];
while ($row = $result->fetch_assoc()) {
    $chats[] = $row;
}

echo json_encode($chats);
