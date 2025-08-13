<?php
// Include config file and establish database connection
require_once "config.php";
require_once "check-banned.php";;
function getAuthorUsername($userID) {
    global $conn;

    // Prepare and execute query to retrieve the username of the author
    $stmt = $conn->prepare("SELECT Username FROM Users WHERE UserID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch and return the author's username
    if ($row = $result->fetch_assoc()) {
        return htmlspecialchars($row["Username"]);
    } else {
        return "Unknown";
    }
}
// Function to retrieve pending posts from the database based on unique PostID
function getPendingPostsByPostID() {
    global $conn;

    // Prepare and execute query to retrieve unique PostIDs of pending posts
    $stmt = $conn->prepare("SELECT DISTINCT PostID FROM Posts WHERE IsApproved = 0");
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if there are any pending posts
    if ($result->num_rows > 0) {
        // Output data of each unique PostID
        while ($row = $result->fetch_assoc()) {
            // Fetch the details of the first post with the current PostID to display
            $postID = $row['PostID'];
            $postDetails = fetchFirstPendingPostByPostID($postID);

            // Fetch author's username using UserID
            $author = getAuthorUsername($postDetails["UserID"]);

            // Display pending post content with a link to view all questions with the same PostID
            echo "<div class='post-item'>";
            echo "<div class='post-details'>";
            echo "<div class='post-title'><a href='view-questions.php?postID=" . $postID . "'>" . htmlspecialchars($postDetails["Title"]) . "</a></div>";
            echo "<div class='post-author'>Author: " . $author . "</div>";
            echo "</div>";
            echo "<div class='post-actions'>";
            echo "<form method='post' action='approve-reject.php'>";
            echo "<input type='hidden' name='post_id' value='" . $postID . "'>";
            echo "<button class='approve' type='submit' name='approve'>Approve</button>";
            echo "<button class='reject' type='submit' name='reject'>Reject</button>";
            echo "</form>";
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "No pending posts found.";
    }
}
// Function to fetch details of the first pending post with a given PostID
function fetchFirstPendingPostByPostID($postID) {
    global $conn;

    // Prepare and execute query to retrieve details of the first pending post with the given PostID
    $stmt = $conn->prepare("SELECT * FROM Posts WHERE PostID = ? LIMIT 1");
    $stmt->bind_param("i", $postID);
    $stmt->execute();
    $result = $stmt->get_result();

    // Return the details of the first pending post with the given PostID
    return $result->fetch_assoc();
}

// Function to retrieve approved posts from the database based on unique PostID
function getApprovedPostsByPostID() {
    global $conn;

    // Prepare and execute query to retrieve unique PostIDs of approved posts
    $stmt = $conn->prepare("SELECT DISTINCT PostID FROM Posts WHERE IsApproved = 1");
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if there are any approved posts
    if ($result->num_rows > 0) {
        // Output data of each unique PostID
        while ($row = $result->fetch_assoc()) {
            // Fetch the details of the first post with the current PostID to display
            $postID = $row['PostID'];
            $postDetails = fetchFirstApprovedPostByPostID($postID);

            // Display approved post content with a link to view all questions with the same PostID
            echo "<div class='post-item'>";
            echo "<div class='post-details'>";
            echo "<div class='post-title'><a href='view-questions.php?postID=" . $postID . "'>" . htmlspecialchars($postDetails["Title"]) . "</a></div>";
            echo "<div class='post-author'>Author: " . htmlspecialchars($postDetails["UserID"]) . "</div>";
            echo "</div>";
            echo "<div class='post-actions'>";
            echo "<form method='post' action='approve-reject.php'>";
            echo "<input type='hidden' name='post_id' value='" . $postID . "'>";
            echo "<button class='delete' type='submit' name='delete'>Delete</button>";
            echo "</form>";
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "No approved posts found.";
    }
}

// Function to fetch details of the first approved post with a given PostID
function fetchFirstApprovedPostByPostID($postID) {
    global $conn;

    // Prepare and execute query to retrieve details of the first approved post with the given PostID
    $stmt = $conn->prepare("SELECT * FROM Posts WHERE PostID = ? LIMIT 1");
    $stmt->bind_param("i", $postID);
    $stmt->execute();
    $result = $stmt->get_result();

    // Return the details of the first approved post with the given PostID
    return $result->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Management - Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="topics-bg">
    <div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php">Home</a>
            <a href="branches.php">Branches</a>
            <a href="about.php">About</a>
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
            <a class="active" href="post-management.php">Post Management</a>
            <a href="user-management.php">User Management</a>
            <a href="report.php">Report Management</a>
            <a href="logout.php">Logout</a>
        </div>
        <div class="admin-content">
            <div class="posted-posts">
                <h2>Posted Posts</h2>
                <?php
                getApprovedPostsByPostID();
                ?>
                <!-- Add more posted posts as needed -->
            </div>
            <div class="pending-posts">
                <h2>Pending Posts</h2>
                <?php
                getPendingPostsByPostID();
                ?>
                <!-- Add more pending posts as needed -->
            </div>
        </div>
    </div>
</body>
</html>
