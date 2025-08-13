<?php
session_start();
require_once "config.php";
require_once "check-banned.php";;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['shoutbox_message'])) {
    $message = $conn->real_escape_string($_POST['shoutbox_message']);
    $user_id = $_SESSION['id'] ?? 0; // Use 0 for guest users
    
    $sql = "INSERT INTO shoutbox_messages (user_id, message) VALUES (?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $user_id, $message);
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Failed to send message"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "error" => "Failed to prepare statement"]);
    }
    exit;
}

// Function to get user type info
function getUserTypeInfo($usertype) {
    switch ($usertype) {
        case 'admin':
            return ['icon' => '👑', 'label' => 'Admin'];
        case 'schooladmin':
            return ['icon' => '🏫', 'label' => 'School Admin'];
        case 'contributor':
            return ['icon' => '📚', 'label' => 'Contributor'];
        default:
            return ['icon' => '👤', 'label' => 'User'];
    }
}

// Fetch latest messages
$sql = "SELECT s.*, u.Username, u.usertype FROM shoutbox_messages s 
        LEFT JOIN Users u ON s.user_id = u.UserID 
        ORDER BY s.timestamp DESC LIMIT 50";
$result = $conn->query($sql);
$shoutbox_messages = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $userTypeInfo = getUserTypeInfo($row['usertype']);
        $shoutbox_messages[] = [
            'username' => $row['Username'] ?? 'Guest',
            'message' => $row['message'],
            'icon' => $userTypeInfo['icon'],
            'label' => $userTypeInfo['label']
        ];
    }
}
echo json_encode(array_reverse($shoutbox_messages));
?>