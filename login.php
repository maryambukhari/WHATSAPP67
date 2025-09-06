<?php
// login.php
session_start();
include 'db.php';

// Only redirect if the user submits the form and is already logged in
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    header("Location: chat.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id']; // Explicitly set user_id
        $_SESSION['username'] = $user['username'];
        // Debug session data
        error_log("Login successful for user_id: " . $user['id'] . ", username: " . $user['username'] . " at " . date('Y-m-d H:i:s'));
        header("Location: chat.php");
        exit();
    } else {
        $error = "Invalid credentials";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WhatsApp Clone</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(to bottom, #00A884, #e5ddd5); 
            margin: 0; 
            padding: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
        }
        .container { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 0 20px rgba(0,0,0,0.1); 
            width: 300px; 
            text-align: center; 
            animation: fadeIn 1s ease-in-out; 
        }
        input { 
            width: 100%; 
            padding: 10px; 
            margin: 10px 0; 
            border: 1px solid #ccc; 
            border-radius: 5px; 
            box-sizing: border-box; 
        }
        button { 
            background: #25D366; 
            color: white; 
            border: none; 
            padding: 10px; 
            border-radius: 5px; 
            cursor: pointer; 
            transition: background 0.3s; 
            width: 100%; 
        }
        button:hover { 
            background: #128C7E; 
        }
        .error { 
            color: red; 
            font-size: 0.9em; 
        }
        a { 
            color: #25D366; 
            text-decoration: none; 
            font-weight: bold; 
        }
        a:hover { 
            text-decoration: underline; 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(-20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        @media (max-width: 480px) { 
            .container { 
                width: 90%; 
                padding: 15px; 
            } 
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login to WhatsApp Clone</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="signup.php">Signup</a></p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p>Already logged in as <?php echo htmlspecialchars($_SESSION['username']); ?>. <a href="chat.php">Go to Chats</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
