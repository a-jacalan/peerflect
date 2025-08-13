<?php
// redeem_reward.php
session_start();
require_once "config.php";
require_once "check-banned.php";

if (!isset($_SESSION['id']) || !isset($_POST['rewardId'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userID = $_SESSION['id'];
$rewardID = intval($_POST['rewardId']);

// Start transaction
$conn->begin_transaction();

try {
    // Get reward details and user's points
    $stmt = $conn->prepare("SELECT r.PointsCost, u.claimable_points 
                           FROM rewards r, users u 
                           WHERE r.RewardID = ? AND u.UserID = ? AND r.IsActive = 1");
    $stmt->bind_param("ii", $rewardID, $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data) {
        throw new Exception('Invalid reward or user');
    }

    if ($data['claimable_points'] < $data['PointsCost']) {
        throw new Exception('Insufficient points');
    }

    // Check if there are any available codes for this reward
    $stmt = $conn->prepare("SELECT CodeID, GiftCardCode 
                           FROM reward_codes 
                           WHERE RewardID = ? AND IsRedeemed = 0 
                           LIMIT 1");
    $stmt->bind_param("i", $rewardID);
    $stmt->execute();
    $result = $stmt->get_result();
    $codeData = $result->fetch_assoc();

    if (!$codeData) {
        throw new Exception('This reward is currently out of stock');
    }

    // Mark the code as redeemed
    $stmt = $conn->prepare("UPDATE reward_codes 
                           SET IsRedeemed = 1, DateRedeemed = CURRENT_TIMESTAMP 
                           WHERE CodeID = ?");
    $stmt->bind_param("i", $codeData['CodeID']);
    $stmt->execute();

    // Insert redemption record
    $stmt = $conn->prepare("INSERT INTO redeemed_rewards (UserID, RewardID, CodeID, CouponCode) 
                           VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $userID, $rewardID, $codeData['CodeID'], $codeData['GiftCardCode']);
    $stmt->execute();

    // Deduct points from user
    $stmt = $conn->prepare("UPDATE users 
                           SET claimable_points = claimable_points - ? 
                           WHERE UserID = ?");
    $stmt->bind_param("ii", $data['PointsCost'], $userID);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'couponCode' => $codeData['GiftCardCode']
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}