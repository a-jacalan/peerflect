<?php
session_start();
require_once "config.php";
require_once "check-banned.php";;

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Get user's claimable points
$userID = $_SESSION['id'];
$stmt = $conn->prepare("SELECT claimable_points FROM users WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$claimablePoints = $userData['claimable_points'];

// Get available rewards
$stmt = $conn->prepare("SELECT * FROM rewards WHERE IsActive = 1 ORDER BY PointsCost ASC");
$stmt->execute();
$rewards = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards Redemption</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .reward-card {
            transition: transform 0.2s;
        }
        .reward-card:hover {
            transform: translateY(-5px);
        }
        .points-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
        }
    </style>
</head>
<body>
    <?php require_once 'topnav.php'?>
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col">
                <h1>Rewards Redemption</h1>
                <div class="alert alert-info">
                    Your Available Points: <strong><?php echo number_format($claimablePoints); ?></strong>
                </div>
                <a href="redemption-history.php" class="btn btn-secondary">View Redemption History</a>
            </div>
        </div>

        <div class="row g-4">
            <?php while ($reward = $rewards->fetch_assoc()): ?>
            <div class="col-md-4">
                <div class="card reward-card h-100">
                    <img src="<?php echo htmlspecialchars($reward['ImageURL']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($reward['RewardName']); ?>" style="height: 200px; object-fit: contain;">
                    <div class="points-badge"><?php echo number_format($reward['PointsCost']); ?> pts</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($reward['RewardName']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($reward['Description']); ?></p>
                        <button class="btn btn-primary redeem-btn" 
                                data-reward-id="<?php echo $reward['RewardID']; ?>"
                                data-points-cost="<?php echo $reward['PointsCost']; ?>"
                                <?php echo ($claimablePoints < $reward['PointsCost']) ? 'disabled' : ''; ?>>
                            <?php echo ($claimablePoints < $reward['PointsCost']) ? 'Insufficient Points' : 'Redeem Reward'; ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Redemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to redeem this reward?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmRedeem">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let selectedRewardId = null;
            
            $('.redeem-btn').click(function() {
                selectedRewardId = $(this).data('reward-id');
                $('#confirmationModal').modal('show');
            });

            $('#confirmRedeem').click(function() {
                if (selectedRewardId) {
                    $.ajax({
                        url: 'redeem_reward.php',
                        method: 'POST',
                        data: {
                            rewardId: selectedRewardId
                        },
                        success: function(response) {
                            const data = JSON.parse(response);
                            if (data.success) {
                                alert('Reward redeemed successfully! Your coupon code is: ' + data.couponCode);
                                location.reload(); // Reload to update points
                            } else {
                                alert(data.message || 'Error redeeming reward');
                            }
                            $('#confirmationModal').modal('hide');
                        },
                        error: function() {
                            alert('Error processing request');
                            $('#confirmationModal').modal('hide');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>