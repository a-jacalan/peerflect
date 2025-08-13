<?php
session_start();
require_once "config.php";
require_once "check-banned.php";;

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to report users']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id']) && isset($_POST['reason']) && isset($_POST['message_id'])) {
    $reported_user_id = $_POST['user_id'];
    $reporter_id = $_SESSION['id'];
    $reason = $_POST['reason'];
    $message_id = $_POST['message_id'];
    
    // Prevent users from reporting themselves
    if ($reported_user_id == $reporter_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot report yourself']);
        exit;
    }
    
    // Insert into violations table
    $sql = "INSERT INTO violations (PostID, UserID, MessageID, Reason, Status, report_type) 
            VALUES (NULL, ?, ?, ?, 'Pending', 'user')";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iis", $reported_user_id, $message_id, $reason);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>