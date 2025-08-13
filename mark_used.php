<?php
// mark_used.php
session_start();
require_once "config.php";
require_once "check-banned.php";;

if (!isset($_SESSION['id']) || !isset($_POST['redemption_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userID = $_SESSION['id'];
$redemptionID = intval($_POST['redemption_id']);

// Verify the redemption belongs to the user and update its status
$stmt = $conn->prepare("
    UPDATE redeemed_rewards 
    SET IsUsed = 1 
    WHERE RedemptionID = ? AND UserID = ? AND IsUsed = 0
");

$stmt->bind_param("ii", $redemptionID, $userID);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Coupon marked as used successfully']);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unable to mark coupon as used']);
}

$stmt->close();
$conn->close();
?>