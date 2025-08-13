<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to user dashboard
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("location: user-dashboard.php");
    exit;
}

// Include database connection
require_once "config.php";
require_once "check-banned.php";;

// Updated function to retrieve reported posts from the Violations table
function getReportedPosts($conn, $page = 1, $postsPerPage = 3) {
    $offset = ($page - 1) * $postsPerPage;
    
    $query = "SELECT v.ViolationID, v.PostID, v.Reason, v.ReportedAt, v.Status, p.Title, u.Username AS Author 
              FROM Violations v
              JOIN Posts p ON v.PostID = p.PostID
              JOIN Users u ON p.UserID = u.UserID
              WHERE v.Status = 'Pending' AND v.report_type = 'post'
              ORDER BY v.ReportedAt DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $postsPerPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        echo "<div class='post-item'>";
        echo "<div class='checkbox-column'>";
        echo "<input type='checkbox' name='selected_posts[]' value='" . $row['ViolationID'] . "' class='post-checkbox'>";
        echo "</div>";
        echo "<div class='post-details'>";
        echo '<div class="post-title"><a href="post.php?postID=' . htmlspecialchars($row['PostID']) . '">' . htmlspecialchars($row['Title']) . '</a></div>';
        echo "<div class='post-author'>Author: " . htmlspecialchars($row['Author']) . "</div>";
        echo "<div class='report-reason'>Reason: " . htmlspecialchars($row['Reason']) . "</div>";
        echo "<div class='report-date'>Reported: " . htmlspecialchars($row['ReportedAt']) . "</div>";
        echo "</div>";
        echo "<div class='post-actions'>";
        echo "<button type='submit' name='dismiss_post' value='" . $row['ViolationID'] . "' class='icon-button dismiss-icon' title='Dismiss Report'>";
        echo "<i class='fas fa-check'></i>";
        echo "</button>";
        echo "<button type='submit' name='remove_post' value='" . $row['ViolationID'] . "' class='icon-button delete-icon' title='Remove Post'>";
        echo "<i class='fas fa-trash-alt'></i>";
        echo "</button>";
        echo "</div>";
        echo "</div>";
    }

    $stmt->close();
}

// Updated function to retrieve reported users from the Violations table
function getReportedUsers($conn, $page = 1, $usersPerPage = 3) {
    $offset = ($page - 1) * $usersPerPage;
    
    $query = "SELECT v.ViolationID, v.UserID, v.Reason, v.ReportedAt, v.Status, u.Username, sm.message
              FROM Violations v
              JOIN Users u ON v.UserID = u.UserID
              LEFT JOIN shoutbox_messages sm ON v.MessageID = sm.id
              WHERE v.Status = 'Pending' AND v.report_type = 'user'
              ORDER BY v.ReportedAt DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $usersPerPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        echo "<div class='user-item'>";
        echo "<div class='checkbox-column'>";
        echo "<input type='checkbox' name='selected_users[]' value='" . $row['ViolationID'] . "' class='user-checkbox'>";
        echo "</div>";
        echo "<div class='user-details'>";
        echo "<div class='username'>" . htmlspecialchars($row['Username']) . "</div>";
        if ($row['message']) {
            echo "<div class='reported-message'>Reported Message: " . htmlspecialchars($row['message']) . "</div>";
        }
        echo "<div class='report-reason'>Reason: " . htmlspecialchars($row['Reason']) . "</div>";
        echo "<div class='report-date'>Reported: " . htmlspecialchars($row['ReportedAt']) . "</div>";
        echo "</div>";
        echo "<div class='user-actions'>";
        echo "<button type='submit' name='dismiss_user' value='" . $row['ViolationID'] . "' class='icon-button dismiss-icon' title='Dismiss Report'>";
        echo "<i class='fas fa-check'></i>";
        echo "</button>";
        echo "<button type='submit' name='ban_user' value='" . $row['ViolationID'] . "' class='icon-button ban-icon' title='Ban User'>";
        echo "<i class='fas fa-ban'></i>";
        echo "</button>";
        echo "</div>";
        echo "</div>";
    }

    $stmt->close();
}

// Updated function to get total number of reported posts
function getTotalReportedPosts($conn) {
    $query = "SELECT COUNT(*) as total FROM Violations WHERE Status = 'Pending' AND report_type = 'post'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Updated function to get total number of reported users
function getTotalReportedUsers($conn) {
    $query = "SELECT COUNT(*) as total FROM Violations WHERE Status = 'Pending' AND report_type = 'user'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Pagination function (unchanged)
function generatePagination($currentPage, $totalPages, $type) {
    $output = '<div class="pagination">';
    $output .= '<a href="?' . $type . '_page=1"' . ($currentPage == 1 ? ' class="active"' : '') . '>1</a>';
    if ($totalPages > 1) {
        $output .= '<a href="?' . $type . '_page=2"' . ($currentPage == 2 ? ' class="active"' : '') . '>2</a>';
    }
    if ($totalPages > 2) {
        $output .= '<a href="?' . $type . '_page=3"' . ($currentPage == 3 ? ' class="active"' : '') . '>3</a>';
    }
    $output .= '</div>';
    return $output;
}

function getBannedUsers($conn, $page = 1, $usersPerPage = 3) {
    $offset = ($page - 1) * $usersPerPage;
    
    $query = "SELECT UserID, Username, Email, FirstName, LastName, school, 
                     ProfileImageURL, points, usertype 
              FROM Users 
              WHERE isBan = 1
              ORDER BY Username ASC
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $usersPerPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $profileImage = $row['ProfileImageURL'] ?? './profile-img/default.jpg';
        echo "<div class='banned-user-item'>";
        echo "<div class='checkbox-column'>";
        echo "<input type='checkbox' name='selected_banned_users[]' value='" . $row['UserID'] . "' class='banned-user-checkbox'>";
        echo "</div>";
        echo "<div class='user-avatar'>";
        echo "<img src='" . htmlspecialchars($profileImage) . "' alt='Profile' class='profile-image'>";
        echo "</div>";
        echo "<div class='user-details'>";
        echo "<div class='username'>" . htmlspecialchars($row['Username']) . "</div>";
        echo "<div class='user-fullname'>" . htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) . "</div>";
        echo "<div class='user-info'>";
        echo "<span class='email'>Email: " . htmlspecialchars($row['Email']) . "</span>";
        if ($row['school']) {
            echo "<span class='school'>School: " . htmlspecialchars($row['school']) . "</span>";
        }
        echo "<span class='user-type'>Type: " . htmlspecialchars(ucfirst($row['usertype'])) . "</span>";
        echo "<span class='points'>Points: " . htmlspecialchars($row['points']) . "</span>";
        echo "</div>";
        echo "</div>";
        echo "<div class='user-actions'>";
        echo "<button type='submit' name='unban_user' value='" . $row['UserID'] . "' class='icon-button unban-icon' title='Unban User'>";
        echo "<i class='fas fa-user-check'></i>";
        echo "</button>";
        echo "</div>";
        echo "</div>";
    }

    $stmt->close();
}

// Function to get total number of banned users
function getTotalBannedUsers($conn) {
    $query = "SELECT COUNT(*) as total FROM Users WHERE isBan = 1";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Function to safely get POST data
    function getPostValue($key) {
        return isset($_POST[$key]) ? $_POST[$key] : null;
    }

    // Function to handle database errors
    function handleDatabaseError($conn, $error) {
        $conn->rollback();
        echo "<script>alert('Error: " . $conn->real_escape_string($error->getMessage()) . "');</script>";
    }

    // Handle Post Actions
    $dismissPost = getPostValue('dismiss_post');
    $removePost = getPostValue('remove_post');
    $bulkDismissPosts = getPostValue('bulk_dismiss_posts');
    $bulkRemovePosts = getPostValue('bulk_remove_posts');

    // Handle User Actions
    $dismissUser = getPostValue('dismiss_user');
    $banUser = getPostValue('ban_user');
    $bulkDismissUsers = getPostValue('bulk_dismiss_users');
    $bulkBanUsers = getPostValue('bulk_ban_users');

    if ($dismissPost) {
        // For single post report dismissal
        $stmt = $conn->prepare("DELETE FROM Violations WHERE ViolationID = ?");
        $stmt->bind_param("i", $dismissPost);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($dismissUser) {
        // For single user report dismissal
        $stmt = $conn->prepare("DELETE FROM Violations WHERE ViolationID = ?");
        $stmt->bind_param("i", $dismissUser);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($bulkDismissPosts && isset($_POST['selected_posts'])) {
        // For bulk post report dismissal
        $violationIDs = implode(',', array_map('intval', $_POST['selected_posts']));
        $conn->query("DELETE FROM Violations WHERE ViolationID IN ($violationIDs)");
        
        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($bulkDismissUsers && isset($_POST['selected_users'])) {
        // For bulk user report dismissal
        $violationIDs = implode(',', array_map('intval', $_POST['selected_users']));
        $conn->query("DELETE FROM Violations WHERE ViolationID IN ($violationIDs)");
        
        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Handle Ban User Action
    if ($banUser) {
        $conn->begin_transaction();
        try {
            // First get the UserID from the Violations table using ViolationID
            $stmt = $conn->prepare("SELECT UserID FROM Violations WHERE ViolationID = ?");
            $stmt->bind_param("i", $banUser);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
            $userID = $userData['UserID'];
            $stmt->close();

            // Now ban the user using the retrieved UserID
            $stmt = $conn->prepare("UPDATE Users SET isBan = 1 WHERE UserID = ?");
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $stmt->close();

            // Update or remove the violation record
            $stmt = $conn->prepare("DELETE FROM Violations WHERE ViolationID = ?");
            $stmt->bind_param("i", $banUser);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            echo "<script>
                alert('User has been banned successfully.');
                window.location.href = 'violation-reports.php';
            </script>";
            exit;
        } catch (Exception $e) {
            handleDatabaseError($conn, $e);
        }
    } elseif ($bulkBanUsers && isset($_POST['selected_users'])) {
        $conn->begin_transaction();
        try {
            // Get UserIDs from Violations table first
            $violationIDs = implode(',', array_map('intval', $_POST['selected_users']));
            $result = $conn->query("SELECT UserID FROM Violations WHERE ViolationID IN ($violationIDs)");
            $userIDs = [];
            while ($row = $result->fetch_assoc()) {
                $userIDs[] = $row['UserID'];
            }
            
            if (!empty($userIDs)) {
                $userIDsStr = implode(',', $userIDs);
                // Ban the users
                $conn->query("UPDATE Users SET isBan = 1 WHERE UserID IN ($userIDsStr)");
                // Remove the violations
                $conn->query("DELETE FROM Violations WHERE ViolationID IN ($violationIDs)");
            }
            
            $conn->commit();
            echo "<script>
                alert('Selected users have been banned successfully.');
                window.location.href = 'violation-reports.php';
            </script>";
            exit;
        } catch (Exception $e) {
            handleDatabaseError($conn, $e);
        }
    }

    // Handle Unban User Action
    $unbanUser = getPostValue('unban_user');
    $bulkUnbanUsers = getPostValue('bulk_unban_users');

    if ($unbanUser) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE Users SET isBan = 0 WHERE UserID = ?");
            $stmt->bind_param("i", $unbanUser);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            echo "<script>
                alert('User has been unbanned successfully.');
                window.location.href = 'violation-reports.php';
            </script>";
            exit;
        } catch (Exception $e) {
            handleDatabaseError($conn, $e);
        }
    } elseif ($bulkUnbanUsers && isset($_POST['selected_banned_users'])) {
        $conn->begin_transaction();
        try {
            $userIDs = implode(',', array_map('intval', $_POST['selected_banned_users']));
            $conn->query("UPDATE Users SET isBan = 0 WHERE UserID IN ($userIDs)");
            
            $conn->commit();
            echo "<script>alert('Selected users have been unbanned successfully.');</script>";
        } catch (Exception $e) {
            handleDatabaseError($conn, $e);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Violation Reports - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .banned-users {
            margin-top: 2rem;
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 100%;
        }

        .banned-user-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            justify-content:space-between;
        }

        .banned-user-item:last-child {
            border-bottom: none;
        }

        .unban-icon {
            color: #28a745;
        }

        .user-info {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .user-email {
            color: #666;
            font-size: 0.9rem;
            margin: 0.25rem 0;
        }
        .user-type {
            background-color: #6c757d;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            text-transform: capitalize;
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
                <a class="active" href="admin.php">Admin</a>
            </div>
        </div>
    </div>
    <div class="admin-container">
        <div class="admin-nav">
            <a href="admin.php">Admin Dashboard</a>
            <a href="post-management.php">Post Management</a>
            <a href="user-management.php">User Management</a>
            <a class="active" href="violation-reports.php">Violation Reports</a>
            <a href="add-rewards.php">Rewards Management</a>
            <a href="admin-log.php">Log History</a>
            <a href="logout.php">Logout</a>
        </div>
        <div class="admin-content">
            <div class="reported-posts">
                <h2>Reported Posts</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="bulk-actions">
                        <input type="checkbox" id="select-all-posts">
                        <label for="select-all-posts" class="select-all-label">Select All</label>
                        <button type="submit" name="bulk_dismiss_posts" class="icon-button dismiss-icon" title="Dismiss Selected">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="submit" name="bulk_remove_posts" class="icon-button delete-icon" title="Remove Selected" onclick="return confirm('Are you sure you want to remove the selected posts?');">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <?php
                    $postPage = isset($_GET['post_page']) ? (int)$_GET['post_page'] : 1;
                    getReportedPosts($conn, $postPage);
                    $totalReportedPosts = getTotalReportedPosts($conn);
                    $totalPostPages = ceil($totalReportedPosts / 3);
                    echo generatePagination($postPage, $totalPostPages, 'post');
                    ?>
                </form>
            </div>
            <div class="reported-users">
                <h2>Reported Users</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="bulk-actions">
                        <input type="checkbox" id="select-all-users">
                        <label for="select-all-users" class="select-all-label">Select All</label>
                        <button type="submit" name="bulk_dismiss_users" class="icon-button dismiss-icon" title="Dismiss Selected">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="submit" name="bulk_ban_users" class="icon-button ban-icon" title="Ban Selected" onclick="return confirm('Are you sure you want to ban the selected users?');">
                            <i class="fas fa-ban"></i>
                        </button>
                    </div>
                    <?php
                    $userPage = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
                    getReportedUsers($conn, $userPage);
                    $totalReportedUsers = getTotalReportedUsers($conn);
                    $totalUserPages = ceil($totalReportedUsers / 3);
                    echo generatePagination($userPage, $totalUserPages, 'user');
                    ?>
                </form>
            </div>
            <div class="banned-users">
                <h2>Banned Users</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="bulk-actions">
                        <input type="checkbox" id="select-all-banned">
                        <label for="select-all-banned" class="select-all-label">Select All</label>
                        <button type="submit" name="bulk_unban_users" class="icon-button unban-icon" title="Unban Selected" onclick="return confirm('Are you sure you want to unban the selected users?');">
                            <i class="fas fa-user-check"></i>
                        </button>
                    </div>
                    <?php
                    $bannedPage = isset($_GET['banned_page']) ? (int)$_GET['banned_page'] : 1;
                    getBannedUsers($conn, $bannedPage);
                    $totalBannedUsers = getTotalBannedUsers($conn);
                    $totalBannedPages = ceil($totalBannedUsers / 3);
                    echo generatePagination($bannedPage, $totalBannedPages, 'banned');
                    ?>
                </form>
            </div>
        </div>
    </div>
    <script>
        // JavaScript for "Select All" functionality
        document.getElementById('select-all-posts').addEventListener('change', function() {
            var checkboxes = document.getElementsByClassName('post-checkbox');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });

        document.getElementById('select-all-users').addEventListener('change', function() {
            var checkboxes = document.getElementsByClassName('user-checkbox');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });
                // Add handler for banned users select all
                document.getElementById('select-all-banned').addEventListener('change', function() {
            var checkboxes = document.getElementsByClassName('banned-user-checkbox');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });
    </script>
    <script src="js/transition.js"></script>
</body>
</html>