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

// Handle delete and bulk delete actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete'])) {
        $postID = $_POST['delete'];
        deletePost($postID);
    } elseif (isset($_POST['bulk_delete'])) {
        if (isset($_POST['selected_posts']) && is_array($_POST['selected_posts'])) {
            foreach ($_POST['selected_posts'] as $postID) {
                deletePost($postID);
            }
        }
    }
}

function deletePost($postID) {
    global $conn;
    
    // Start a transaction
    $conn->begin_transaction();

    try {
        // Delete related records from the violations table
        $stmt = $conn->prepare("DELETE FROM violations WHERE PostID = ?");
        $stmt->bind_param("i", $postID);
        $stmt->execute();

        // Delete related records from other tables if they exist
        $stmt = $conn->prepare("DELETE FROM comments WHERE PostID = ?");
        $stmt->bind_param("i", $postID);
        $stmt->execute();

        // Delete related records from other tables if they exist
        $stmt = $conn->prepare("DELETE FROM ratings WHERE PostID = ?");
        $stmt->bind_param("i", $postID);
        $stmt->execute();

        // Finally, delete the post itself
        $stmt = $conn->prepare("DELETE FROM Posts WHERE PostID = ?");
        $stmt->bind_param("i", $postID);
        $stmt->execute();

        // If we've made it this far without an exception, commit the transaction
        $conn->commit();
        $_SESSION['notification'] = ['message' => 'Post and related data deleted successfully.', 'type' => 'success'];
    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        $conn->rollback();
        $_SESSION['notification'] = ['message' => 'Error deleting post: ' . $e->getMessage(), 'type' => 'error'];
    }
}

function getAuthorUsername($userID) {
    global $conn;
    $stmt = $conn->prepare("SELECT Username FROM Users WHERE UserID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return htmlspecialchars($row["Username"]);
    } else {
        return "Unknown";
    }
}

function fetchPostByPostID($postID) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM Posts WHERE PostID = ? LIMIT 1");
    $stmt->bind_param("i", $postID);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getTotalPosts() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT PostID) as total FROM Posts");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

function generatePagination($currentPage, $totalPages) {
    $output = '<div class="pagination">';
    
    // Always show first page
    $output .= '<a href="?page=1"' . ($currentPage == 1 ? ' class="active"' : '') . '>1</a>';
    
    $startPage = max(2, $currentPage - 1);
    $endPage = min($totalPages - 1, $currentPage + 1);
    
    if ($startPage > 2) {
        $output .= '<span class="ellipsis">...</span>';
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $output .= '<a href="?page=' . $i . '"' . ($i == $currentPage ? ' class="active"' : '') . '>' . $i . '</a>';
    }
    
    if ($endPage < $totalPages - 1) {
        $output .= '<span class="ellipsis">...</span>';
    }
    
    // Always show last page if it's not already shown
    if ($totalPages > 1 && $endPage < $totalPages) {
        $output .= '<a href="?page=' . $totalPages . '"' . ($currentPage == $totalPages ? ' class="active"' : '') . '>' . $totalPages . '</a>';
    }
    
    $output .= '</div>';
    return $output;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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

    <div id="notification" class="notification"></div>

    <div class="admin-container">
        <div class="admin-nav">
            <a href="admin.php">Admin Dashboard</a>
            <a class="active" href="post-management.php">Post Management</a>
            <a href="user-management.php">User Management</a>
            <a href="violation-reports.php">Violation Reports</a>
            <a href="add-rewards.php">Rewards Management</a>
            <a href="admin-log.php">Log History</a>
            <a href="logout.php">Logout</a>
        </div>
        <div class="admin-content">
            <div class="posts">
                <h2>Manage Posts</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="bulk-actions">
                        <input type="checkbox" id="select-all-posts">
                        <label for="select-all-posts" class="select-all-label">Select All</label>
                        <button type="submit" name="bulk_delete" class="icon-button delete-icon" title="Delete Selected" onclick="return confirm('Are you sure you want to delete the selected posts?');">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <?php
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $postsPerPage = 5;
                    $offset = ($page - 1) * $postsPerPage;
                    
                    $stmt = $conn->prepare("SELECT DISTINCT PostID FROM Posts LIMIT ? OFFSET ?");
                    $stmt->bind_param("ii", $postsPerPage, $offset);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $postID = $row['PostID'];
                        $postDetails = fetchPostByPostID($postID);
                        $author = getAuthorUsername($postDetails["UserID"]);
                        echo "<div class='post-item'>";
                        echo "<div class='checkbox-column'>";
                        echo "<input type='checkbox' name='selected_posts[]' value='" . $postID . "' class='post-checkbox'>";
                        echo "</div>";
                        echo "<div class='post-details'>";
                        echo "<div class='post-title'><a href='post.php?postID=" . $postID . "'>" . htmlspecialchars($postDetails["Title"]) . "</a></div>";
                        echo "<div class='post-author'>Author: " . $author . "</div>";
                        echo "</div>";
                        echo "<div class='post-actions'>";
                        echo "<button type='submit' name='delete' value='" . $postID . "' class='icon-button delete-icon' title='Delete Post' onclick='return confirm(\"Are you sure you want to delete this post?\");'>";
                        echo "<i class='fas fa-trash-alt'></i>";
                        echo "</button>";
                        echo "</div>";
                        echo "</div>";
                    }
                    
                    $totalPosts = getTotalPosts();
                    $totalPages = ceil($totalPosts / $postsPerPage);
                    echo generatePagination($page, $totalPages);
                    ?>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showNotification(message, type) {
            var notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.style.display = 'block';
            setTimeout(function() {
                notification.style.display = 'none';
            }, 3000);
        }

        <?php
        if (isset($_SESSION['notification'])) {
            echo "showNotification('" . $_SESSION['notification']['message'] . "', '" . $_SESSION['notification']['type'] . "');";
            unset($_SESSION['notification']);
        }
        ?>

        // JavaScript for "Select All" functionality
        document.getElementById('select-all-posts').addEventListener('change', function() {
            var checkboxes = document.getElementsByClassName('post-checkbox');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });
    </script>
    <script src="js/transition.js"></script>
</body>
</html>