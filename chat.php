<?php
// chat.php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Debug session data
error_log("Chat page loaded with user_id: " . $_SESSION['user_id'] . ", username: " . $_SESSION['username'] . " at " . date('Y-m-d H:i:s'));

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get all users except self for contacts
$contacts_sql = "SELECT id, username FROM users WHERE id != ?";
$stmt = $conn->prepare($contacts_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$contacts_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Clone</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #1a73e8, #4a90e2);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        #sidebar {
            width: 30%;
            background: linear-gradient(45deg, #2c3e50, #3498db);
            border-right: 2px solid #e74c3c;
            overflow-y: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }
        #sidebar:hover {
            transform: scale(1.02);
        }
        #chat {
            width: 70%;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #ecf0f1, #bdc3c7);
        }
        #chat-header {
            background: linear-gradient(90deg, #e74c3c, #e67e22);
            color: #fff;
            padding: 15px;
            display: flex;
            align-items: center;
            font-size: 1.2em;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            animation: headerGlow 3s infinite;
        }
        @keyframes headerGlow {
            0% { box-shadow: 0 2px 10px rgba(230, 126, 34, 0.5); }
            50% { box-shadow: 0 2px 10px rgba(231, 76, 60, 0.7); }
            100% { box-shadow: 0 2px 10px rgba(230, 126, 34, 0.5); }
        }
        #messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: url('https://web.whatsapp.com/img/bg-chat-tile-light_686b94a022c235e8.png') repeat;
            animation: bgFade 10s infinite;
        }
        @keyframes bgFade {
            0% { background-color: #ecf0f1; }
            50% { background-color: #bdc3c7; }
            100% { background-color: #ecf0f1; }
        }
        #input {
            display: flex;
            padding: 15px;
            background: linear-gradient(90deg, #34495e, #2ecc71);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }
        #input input {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 25px;
            margin-right: 10px;
            background: #fff;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
        }
        #input input:focus {
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.2), 0 0 5px #2ecc71;
            outline: none;
        }
        #input button {
            background: linear-gradient(45deg, #e74c3c, #e67e22);
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            transition: transform 0.3s ease, background 0.3s ease;
        }
        #input button:hover {
            transform: scale(1.05);
            background: linear-gradient(45deg, #c0392b, #d35400);
        }
        .contact {
            padding: 15px;
            border-bottom: 1px solid #e74c3c;
            cursor: pointer;
            color: #ecf0f1;
            transition: background 0.3s ease, color 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .contact:hover {
            background: #e67e22;
            color: #fff;
        }
        .contact::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: left 0.5s ease;
        }
        .contact:hover::before {
            left: 100%;
        }
        .message {
            margin: 10px 0;
            padding: 12px 15px;
            border-radius: 15px;
            max-width: 60%;
            position: relative;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .sent { background: linear-gradient(90deg, #2ecc71, #27ae60); align-self: flex-end; color: #fff; }
        .received { background: linear-gradient(90deg, #ecf0f1, #bdc3c7); align-self: flex-start; }
        .timestamp, .status {
            font-size: 0.7em;
            color: #7f8c8d;
            display: block;
            margin-top: 5px;
        }
        .no-chats { text-align: center; color: #7f8c8d; padding: 20px; }
        /* Animation for new messages only */
        #messages .message { animation: none; }
        @keyframes messageIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        #messages .message.new { animation: messageIn 0.3s ease-in-out; }
        @media (max-width: 768px) {
            #sidebar { width: 100%; }
            #chat { width: 100%; display: none; }
        }
    </style>
</head>
<body>
    <div id="sidebar">
        <h2 style="color: #e74c3c; text-align: center; padding: 10px; background: rgba(0, 0, 0, 0.2);">Chats</h2>
        <div id="chat-list">
            <?php
            $chat_sql = "SELECT u.id, u.username, m.message AS last_message, m.timestamp 
                        FROM users u 
                        LEFT JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id) AND m.receiver_id = ? 
                        WHERE u.id != ? 
                        GROUP BY u.id 
                        ORDER BY m.timestamp DESC";
            $chat_stmt = $conn->prepare($chat_sql);
            $chat_stmt->bind_param("ii", $user_id, $user_id);
            $chat_stmt->execute();
            $chat_result = $chat_stmt->get_result();
            if ($chat_result->num_rows > 0) {
                while ($chat = $chat_result->fetch_assoc()) {
                    echo "<div class='contact' onclick=\"openChat({$chat['id']}, '{$chat['username']}')\">";
                    echo htmlspecialchars($chat['username']) . " (Last: " . (empty($chat['last_message']) ? 'No messages' : htmlspecialchars(substr($chat['last_message'], 0, 20))) . ")";
                    echo "</div>";
                }
            } else {
                echo "<div class='no-chats'>No chats yet. Start a conversation!</div>";
            }
            ?>
        </div>
        <h3 style="color: #e67e22; text-align: center; padding: 10px; background: rgba(0, 0, 0, 0.2);">Contacts</h3>
        <?php
        if ($contacts_result->num_rows > 0) {
            $contacts_result->data_seek(0); // Reset pointer
            while ($contact = $contacts_result->fetch_assoc()): ?>
                <div class="contact" onclick="openChat(<?php echo $contact['id']; ?>, '<?php echo htmlspecialchars($contact['username']); ?>')">
                    <?php echo htmlspecialchars($contact['username']); ?>
                </div>
            <?php endwhile;
        } else {
            echo "<div class='no-chats'>No contacts yet. Sign up more users!</div>";
        }
        ?>
    </div>
    <div id="chat" style="display: none;">
        <div id="chat-header">
            <span id="chat-username"></span>
        </div>
        <div id="messages"></div>
        <div id="input">
            <input type="text" id="message-input" placeholder="Type a message">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>
    <script>
        let currentChatId = null;
        let pollInterval;
        let lastMessages = [];

        function openChat(id, name) {
            currentChatId = id;
            document.getElementById('chat').style.display = 'flex';
            document.getElementById('chat-username').innerText = name;
            loadMessages();
            clearInterval(pollInterval);
            pollInterval = setInterval(loadMessages, 5000); // Poll every 5 seconds
            markAsRead();
        }

        function loadMessages() {
            if (!currentChatId) return;
            fetch('get_messages.php?receiver_id=' + currentChatId)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok: ' + response.status);
                    return response.text();
                })
                .then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON response: ' + text.substring(0, 50));
                    }
                    let messagesDiv = document.getElementById('messages');
                    let newMessages = [];

                    if (data.length === 0) {
                        messagesDiv.innerHTML = '<div class="no-chats">No messages yet. Send one!</div>';
                        lastMessages = [];
                    } else {
                        // Identify new messages by comparing with lastMessages using id or timestamp
                        data.forEach(msg => {
                            if (!lastMessages.some(m => m.id === msg.id)) {
                                newMessages.push(msg);
                            }
                        });

                        if (newMessages.length > 0 || messagesDiv.innerHTML === '') {
                            messagesDiv.innerHTML = ''; // Clear only if new messages or initial load
                            data.forEach(msg => {
                                let div = document.createElement('div');
                                div.classList.add('message', msg.sender_id == <?php echo $user_id; ?> ? 'sent' : 'received');
                                if (newMessages.some(m => m.id === msg.id)) div.classList.add('new');
                                div.innerText = msg.message;
                                let ts = document.createElement('span');
                                ts.classList.add('timestamp');
                                ts.innerText = new Date(msg.timestamp).toLocaleTimeString();
                                let status = document.createElement('span');
                                status.classList.add('status');
                                status.innerText = msg.status || 'sent';
                                div.appendChild(ts);
                                div.appendChild(status);
                                messagesDiv.appendChild(div);
                            });
                            lastMessages = data.map(m => ({ id: m.id, timestamp: m.timestamp })); // Track by id and timestamp
                        }
                    }
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    document.getElementById('messages').innerHTML = '<div class="no-chats">Error loading messages. Try again!</div>';
                });
        }

        function sendMessage() {
            let input = document.getElementById('message-input');
            if (!currentChatId || input.value.trim() === '') {
                alert('Please select a contact and type a message!');
                return;
            }
            fetch('send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'receiver_id=' + encodeURIComponent(currentChatId) + '&message=' + encodeURIComponent(input.value)
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Server error: ' + response.status + ' - ' + (text || 'No response body'));
                    });
                }
                return response.text();
            })
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid JSON response: ' + text.substring(0, 50));
                }
                if (data.success) {
                    input.value = '';
                    loadMessages();
                } else {
                    throw new Error(data.error || 'Unknown error sending message');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Error sending message: ' + error.message + '. Check console for details!');
            });
        }

        function markAsRead() {
            if (!currentChatId) return;
            fetch('mark_read.php?receiver_id=' + currentChatId)
                .catch(error => console.error('Error marking as read:', error));
        }

        // Load initial chat list and auto-open first contact if available
        document.addEventListener('DOMContentLoaded', () => {
            let contacts = document.querySelectorAll('.contact');
            if (contacts.length > 0) {
                contacts[0].click(); // Auto-open first contact
            }
        });
    </script>
</body>
</html>
