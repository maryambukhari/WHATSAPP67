<?php
// send_message.php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Set error reporting to log errors but not display notices
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/whatsapp_error.log'); // Local error log file

$response = ['success' => false];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized: No session user ID', 403);
    }

    $sender_id = $_SESSION['user_id'];
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : null;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if ($receiver_id === null || $message === '') {
        throw new Exception('Invalid receiver ID or empty message', 400);
    }

    // Test database connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error, 500);
    }

    // Verify sender exists
    $sender_check_sql = "SELECT id FROM users WHERE id = ?";
    $sender_stmt = $conn->prepare($sender_check_sql);
    if (!$sender_stmt) {
        throw new Exception('Database prepare failed for sender check: ' . $conn->error, 500);
    }
    $sender_stmt->bind_param("i", $sender_id);
    if (!$sender_stmt->execute()) {
        throw new Exception('Database execute failed for sender check: ' . $conn->error, 500);
    }
    $sender_result = $sender_stmt->get_result();
    if ($sender_result->num_rows === 0) {
        throw new Exception('Invalid sender ID: Sender not found in users table', 400);
    }
    $sender_stmt->close();

    // Verify receiver exists
    $receiver_check_sql = "SELECT id FROM users WHERE id = ?";
    $receiver_stmt = $conn->prepare($receiver_check_sql);
    if (!$receiver_stmt) {
        throw new Exception('Database prepare failed for receiver check: ' . $conn->error, 500);
    }
    $receiver_stmt->bind_param("i", $receiver_id);
    if (!$receiver_stmt->execute()) {
        throw new Exception('Database execute failed for receiver check: ' . $conn->error, 500);
    }
    $receiver_result = $receiver_stmt->get_result();
    if ($receiver_result->num_rows === 0) {
        throw new Exception('Invalid receiver ID', 400);
    }
    $receiver_stmt->close();

    $sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error, 500);
    }
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);

    if ($stmt->execute()) {
        $response = ['success' => true, 'message_id' => $conn->insert_id];
        http_response_code(200);
    } else {
        throw new Exception('Failed to save message: ' . $conn->error, 500);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    $response['error'] = $e->getMessage();
    error_log("Send message error [{$e->getCode()}]: {$e->getMessage()} at " . date('Y-m-d H:i:s') . " - Trace: " . $e->getTraceAsString());
}

echo json_encode($response);
if (isset($stmt)) $stmt->close();
if (isset($sender_stmt)) $sender_stmt->close();
if (isset($receiver_stmt)) $receiver_stmt->close();
$conn->close();
?>
