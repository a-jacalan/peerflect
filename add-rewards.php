<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to login page
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config.php";
require_once "check-banned.php";

// Function to handle file upload
function handleFileUpload($file) {
    $targetDir = "rewards-img/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($file["name"]);
    $targetFilePath = $targetDir . time() . '_' . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    // Allow certain file formats
    $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
    if (in_array(strtolower($fileType), $allowTypes)) {
        // Check file size (5MB max)
        if ($file["size"] > 5000000) {
            return array('success' => false, 'message' => 'File is too large. Maximum size is 5MB.');
        }
        
        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
            return array('success' => true, 'path' => $targetFilePath);
        } else {
            return array('success' => false, 'message' => 'Sorry, there was an error uploading your file.');
        }
    } else {
        return array('success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.');
    }
}

// Process form submission for adding new reward
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_reward'])) {
    $rewardName = trim($_POST['reward_name']);
    $description = trim($_POST['description']);
    $pointsCost = intval($_POST['points_cost']);
    
    // Handle file upload
    $uploadResult = array('success' => false, 'message' => 'No file uploaded.');
    if (isset($_FILES["reward_image"]) && $_FILES["reward_image"]["error"] != 4) {
        $uploadResult = handleFileUpload($_FILES["reward_image"]);
    }
    
    if ($uploadResult['success']) {
        $imagePath = $uploadResult['path'];
        
        $stmt = $conn->prepare("INSERT INTO rewards (RewardName, Description, PointsCost, ImageURL) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $rewardName, $description, $pointsCost, $imagePath);
        
        if ($stmt->execute()) {
            $success_message = "Reward added successfully!";
            $newRewardId = $conn->insert_id;
            
            // If codes were provided, add them
            if (!empty($_POST['gift_codes'])) {
                $codes = explode("\n", trim($_POST['gift_codes']));
                $stmt = $conn->prepare("INSERT INTO reward_codes (RewardID, GiftCardCode) VALUES (?, ?)");
                
                foreach ($codes as $code) {
                    $code = trim($code);
                    if (!empty($code)) {
                        $stmt->bind_param("is", $newRewardId, $code);
                        $stmt->execute();
                    }
                }
            }
        } else {
            $error_message = "Error adding reward.";
        }
    } else {
        $error_message = $uploadResult['message'];
    }
}

// Handle delete reward action
if (isset($_POST["delete_reward"])) {
    $rewardID = $_POST["delete_reward"];
    
    $sqlDeleteReward = "DELETE FROM rewards WHERE RewardID = ?";
    $stmtDeleteReward = $conn->prepare($sqlDeleteReward);
    $stmtDeleteReward->bind_param("i", $rewardID);
    
    if ($stmtDeleteReward->execute()) {
        $success_message = "Reward deleted successfully!";
    } else {
        $error_message = "Error deleting reward.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_codes'])) {
    $rewardID = $_POST['reward_id'];
    $newCodes = explode("\n", trim($_POST['new_codes']));
    
    $stmt = $conn->prepare("INSERT INTO reward_codes (RewardID, GiftCardCode) VALUES (?, ?)");
    $codesAdded = 0;
    
    foreach ($newCodes as $code) {
        $code = trim($code);
        if (!empty($code)) {
            $stmt->bind_param("is", $rewardID, $code);
            if ($stmt->execute()) {
                $codesAdded++;
            }
        }
    }
    
    if ($codesAdded > 0) {
        $success_message = "Successfully added " . $codesAdded . " new code(s)!";
    } else {
        $error_message = "No new codes were added.";
    }
}
// Pagination setup
$rewardsPerPage = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $rewardsPerPage;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchParam = "%$search%";

// Fetch rewards with pagination and search
$sqlRewards = "SELECT r.*, COUNT(rc.CodeID) as available_codes 
               FROM rewards r 
               LEFT JOIN reward_codes rc ON r.RewardID = rc.RewardID AND rc.IsRedeemed = 0
               WHERE r.RewardName LIKE ? OR r.Description LIKE ?
               GROUP BY r.RewardID 
               ORDER BY r.RewardID DESC
               LIMIT ? OFFSET ?";

$stmtRewards = $conn->prepare($sqlRewards);
$stmtRewards->bind_param("ssii", $searchParam, $searchParam, $rewardsPerPage, $offset);
$stmtRewards->execute();
$resultRewards = $stmtRewards->get_result();

// Get total number of rewards for pagination
$sqlTotal = "SELECT COUNT(DISTINCT r.RewardID) as total 
             FROM rewards r 
             WHERE r.RewardName LIKE ? OR r.Description LIKE ?";
$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->bind_param("ss", $searchParam, $searchParam);
$stmtTotal->execute();
$totalRewards = $stmtTotal->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRewards / $rewardsPerPage);

// Fetch admin's full name and user type
$adminUsername = $_SESSION["username"];
$sqlAdminInfo = "SELECT CONCAT(FirstName, ' ', LastName) AS FullName, UserType FROM Users WHERE Username = ?";
$stmtAdminInfo = $conn->prepare($sqlAdminInfo);
$stmtAdminInfo->bind_param("s", $adminUsername);
$stmtAdminInfo->execute();
$resultAdminInfo = $stmtAdminInfo->get_result();
$adminInfo = $resultAdminInfo->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .file-upload-container {
            margin-bottom: 20px;
        }
        .file-upload-container input[type="file"] {
            display: block;
            margin-top: 5px;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body class="topics-bg">
    <div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php">Home</a>
            <a href="topics.php">Topics</a>
            <a href="index.php?scroll=about">About</a>
            <div class="search-bar">
                <form action="search-results.php" method="GET">
                    <input type="text" name="q" placeholder="Search...">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="menu-loginreg">
                <a class="active" href="admin.php"><?php echo htmlspecialchars($adminInfo['FullName'] . ' (' . $adminInfo['UserType'] . ')'); ?></a>
            </div>
        </div>
    </div>
    <div class="admin-container">
        <div class="admin-nav">
            <a href="admin.php">Admin Dashboard</a>
            <a href="post-management.php">Post Management</a>
            <a href="user-management.php">User Management</a>
            <a href="violation-reports.php">Violation Reports</a>
            <a class="active" href="add-rewards.php">Rewards Management</a>
            <a href="admin-log.php">Log History</a>
            <a href="logout.php">Logout</a>
        </div>
        <div class="admin-content">
            <div class="rewards">
                <h2>Rewards Management</h2>
                
                <?php if (isset($success_message)): ?>
                    <div class="success-message"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="search-add-container">
                    <form action="" method="get" class="search-form">
                        <input type="text" name="search" placeholder="Search rewards..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                    </form>
                    <button id="addRewardBtn" class="add-user-btn">Add Reward</button>
                </div>

                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Points Cost</th>
                            <th>Available Codes</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($reward = $resultRewards->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reward['RewardID']); ?></td>
                            <td><?php echo htmlspecialchars($reward['RewardName']); ?></td>
                            <td><?php echo htmlspecialchars($reward['Description']); ?></td>
                            <td><?php echo number_format($reward['PointsCost']); ?></td>
                            <td><?php echo $reward['available_codes']; ?></td>
                            <td><?php echo $reward['IsActive'] ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <button type="submit" name="delete_reward" value="<?php echo $reward['RewardID']; ?>" 
                                            class="icon-button delete-icon" title="Delete Reward"
                                            onclick="return confirm('Are you sure you want to delete this reward?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                <button class="icon-button add-codes-icon" title="Add Codes"
                                        onclick="openAddCodesModal(<?php echo $reward['RewardID']; ?>, '<?php echo htmlspecialchars($reward['RewardName'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-plus-circle"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                           <?php echo ($page == $i) ? 'class="active"' : ''; ?>>
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Reward Modal -->
    <div id="addRewardModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Reward</h2>
            <form id="addRewardForm" method="POST" action="" enctype="multipart/form-data">
                <label for="reward_name">Reward Name:</label>
                <input type="text" id="reward_name" name="reward_name" required><br><br>
                
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea><br><br>
                
                <label for="points_cost">Points Cost:</label>
                <input type="number" id="points_cost" name="points_cost" required><br><br>
                
                <div class="file-upload-container">
                    <label for="reward_image">Reward Image:</label>
                    <input type="file" id="reward_image" name="reward_image" accept="image/*" required>
                    <img id="imagePreview" class="preview-image" alt="Image preview">
                </div>
                
                <label for="gift_codes">Gift Card Codes (one per line):</label>
                <textarea id="gift_codes" name="gift_codes" rows="5" 
                          placeholder="Enter each code on a new line"></textarea><br><br>
                
                <input type="submit" name="add_reward" value="Add Reward">
            </form>
        </div>
    </div>
    <div id="addCodesModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddCodesModal()">&times;</span>
            <h2>Add Codes for <span id="rewardNameSpan"></span></h2>
            <form id="addCodesForm" method="POST" action="">
                <input type="hidden" id="reward_id" name="reward_id">
                <label for="new_codes">Enter Gift Card Codes (one per line):</label>
                <textarea id="new_codes" name="new_codes" rows="10" required
                        placeholder="Enter each code on a new line"></textarea>
                <br><br>
                <input type="submit" name="add_codes" value="Add Codes">
            </form>
        </div>
    </div>
    <script>
        // Modal JavaScript
        var modal = document.getElementById("addRewardModal");
        var btn = document.getElementById("addRewardBtn");
        var span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        document.getElementById('reward_image').onchange = function(event) {
            const preview = document.getElementById('imagePreview');
            preview.style.display = 'block';
            preview.src = URL.createObjectURL(event.target.files[0]);
        };

        function openAddCodesModal(rewardId, rewardName) {
            document.getElementById('addCodesModal').style.display = "block";
            document.getElementById('reward_id').value = rewardId;
            document.getElementById('rewardNameSpan').textContent = rewardName;
        }
        
        function closeAddCodesModal() {
            document.getElementById('addCodesModal').style.display = "none";
            document.getElementById('new_codes').value = '';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('addCodesModal')) {
                closeAddCodesModal();
            }
            if (event.target == document.getElementById('addRewardModal')) {
                document.getElementById('addRewardModal').style.display = "none";
            }
        }
    </script>
    <script src="js/transition.js"></script>
</body>
</html>