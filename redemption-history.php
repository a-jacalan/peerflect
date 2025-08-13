<?php
session_start();
require_once "config.php";
require_once "check-banned.php";;

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['id'];

// Initialize filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$dateFilter = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Build the WHERE clause
$whereClause = "WHERE rr.UserID = ?";
$params = [$userID];
$types = "i";

if ($statusFilter != 'all') {
    $whereClause .= " AND rr.IsUsed = ?";
    $params[] = ($statusFilter === 'used') ? 1 : 0;
    $types .= "i";
}

if ($dateFilter != 'all') {
    switch($dateFilter) {
        case 'today':
            $whereClause .= " AND DATE(rr.RedemptionDate) = CURDATE()";
            break;
        case 'week':
            $whereClause .= " AND rr.RedemptionDate >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $whereClause .= " AND rr.RedemptionDate >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
    }
}

if (!empty($searchTerm)) {
    $whereClause .= " AND (r.RewardName LIKE ? OR rr.CouponCode LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

// Pagination
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM redeemed_rewards rr
               JOIN rewards r ON rr.RewardID = r.RewardID
               $whereClause";
$stmt = $conn->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalItems = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Get paginated results
$query = "SELECT 
    rr.RedemptionID,
    rr.RedemptionDate,
    rr.CouponCode,
    rr.IsUsed,
    r.RewardName,
    r.Description,
    r.PointsCost,
    r.ImageURL
FROM redeemed_rewards rr
JOIN rewards r ON rr.RewardID = r.RewardID
$whereClause
ORDER BY rr.RedemptionDate DESC
LIMIT ? OFFSET ?";

$params[] = $itemsPerPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$history = $stmt->get_result();

// Get user's current points
$stmt = $conn->prepare("SELECT claimable_points FROM users WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redemption History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .history-card {
            transition: transform 0.2s;
        }
        .history-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .coupon-code {
            font-family: monospace;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 1.1em;
        }
        .reward-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 8px;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php require_once 'topnav.php'?>
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col">
                <h1>Redemption History</h1>
                <div class="alert alert-info d-flex justify-content-between align-items-center">
                    <span>Current Available Points: <strong><?php echo number_format($userData['claimable_points']); ?></strong></span>
                </div>
                <a href="redeem-rewards.php" class="btn btn-primary">Redeem More Rewards</a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $statusFilter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="available" <?php echo $statusFilter == 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="used" <?php echo $statusFilter == 'used' ? 'selected' : ''; ?>>Used</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <select name="date_range" class="form-select">
                        <option value="all" <?php echo $dateFilter == 'all' ? 'selected' : ''; ?>>All Time</option>
                        <option value="today" <?php echo $dateFilter == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $dateFilter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $dateFilter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search rewards or coupon codes..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>

        <?php if ($history->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($redemption = $history->fetch_assoc()): ?>
                    <div class="col-12">
                        <div class="card history-card position-relative">
                            <div class="card-body d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($redemption['ImageURL']); ?>" 
                                     alt="<?php echo htmlspecialchars($redemption['RewardName']); ?>"
                                     class="reward-image me-4">
                                
                                <div class="flex-grow-1">
                                    <div class="status-badge">
                                        <?php if ($redemption['IsUsed']): ?>
                                            <span class="badge bg-secondary">Used</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h5 class="card-title mb-2"><?php echo htmlspecialchars($redemption['RewardName']); ?></h5>
                                    <p class="card-text text-muted mb-2"><?php echo htmlspecialchars($redemption['Description']); ?></p>
                                    
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="me-3">
                                            <i class="bi bi-clock"></i>
                                            Redeemed: <?php echo date('M d, Y h:i A', strtotime($redemption['RedemptionDate'])); ?>
                                        </span>
                                        <span class="me-3">
                                            <i class="bi bi-coin"></i>
                                            Cost: <?php echo number_format($redemption['PointsCost']); ?> points
                                        </span>
                                    </div>
                                    
                                    <div class="coupon-details">
                                        <strong>Coupon Code:</strong>
                                        <span class="coupon-code">
                                            <?php echo htmlspecialchars($redemption['CouponCode']); ?>
                                        </span>
                                        <button class="btn btn-sm btn-outline-primary ms-2 copy-btn" 
                                                data-coupon="<?php echo htmlspecialchars($redemption['CouponCode']); ?>">
                                            <i class="bi bi-clipboard"></i> Copy
                                        </button>
                                        <?php if (!$redemption['IsUsed']): ?>
                                            <button class="btn btn-sm btn-outline-success ms-2 mark-used-btn" 
                                                    data-redemption-id="<?php echo $redemption['RedemptionID']; ?>">
                                                <i class="bi bi-check-circle"></i> Mark as Used
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?>&status=<?php echo $statusFilter; ?>&date_range=<?php echo $dateFilter; ?>&search=<?php echo urlencode($searchTerm); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&date_range=<?php echo $dateFilter; ?>&search=<?php echo urlencode($searchTerm); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?>&status=<?php echo $statusFilter; ?>&date_range=<?php echo $dateFilter; ?>&search=<?php echo urlencode($searchTerm); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-ticket-perforated" style="font-size: 3rem;"></i>
                <h3 class="mt-3">No Results Found</h3>
                <p class="text-muted">Try adjusting your filters or search terms.</p>
                <a href="redeem_history.php" class="btn btn-primary mt-2">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.copy-btn').click(function() {
                const couponCode = $(this).data('coupon');
                navigator.clipboard.writeText(couponCode).then(() => {
                    const $btn = $(this);
                    $btn.html('<i class="bi bi-check"></i> Copied!');
                    setTimeout(() => {
                        $btn.html('<i class="bi bi-clipboard"></i> Copy');
                    }, 2000);
                });
            });
            
            $('.mark-used-btn').click(function() {
                const $btn = $(this);
                const redemptionId = $btn.data('redemption-id');
                
                if (confirm('Are you sure you want to mark this coupon as used? This action cannot be undone.')) {
                    $.post('mark_used.php', { redemption_id: redemptionId })
                        .done(function(response) {
                            const data = JSON.parse(response);
                            if (data.success) {
                                // Update the UI
                                const $card = $btn.closest('.history-card');
                                $btn.remove();
                                $card.find('.status-badge .badge')
                                    .removeClass('bg-success')
                                    .addClass('bg-secondary')
                                    .text('Used');
                                    
                                // Show success message
                                const alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                    'Coupon marked as used successfully!' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                    '</div>');
                                $('.container').prepend(alert);
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .fail(function() {
                            alert('Error marking coupon as used. Please try again.');
                        });
                }
            });
        });
    </script>
</body>
</html>