<?php
// Include config file and establish database connection
require_once "config.php";
require_once "check-banned.php";;

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["approve"])) {
        // Handle approve action
        $postID = $_POST["post_id"];
        approvePost($postID);
    } elseif (isset($_POST["reject"])) {
        // Handle reject action
        $postID = $_POST["post_id"];
        rejectPost($postID);
    } elseif (isset($_POST["delete"])) {
        // Handle delete action
        $postID = $_POST["post_id"];
        deletePost($postID);
    }
}

// Function to approve a post by its ID
function approvePost($postID) {
    global $conn;

    // Prepare and execute query to update the post as approved
    $stmt = $conn->prepare("UPDATE Posts SET IsApproved = 1 WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    if ($stmt->execute()) {
        logActivity($postID, 'posted');
    }

    // Redirect back to the post management page after approval
    header("Location: post-management.php");
    exit();
}

// Function to reject a post by its ID
function rejectPost($postID) {
    global $conn;

    // Prepare and execute query to delete the post
    $stmt = $conn->prepare("DELETE FROM Posts WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    $stmt->execute();

    // Redirect back to the post management page after rejection
    header("Location: post-management.php");
    exit();
}

// Function to delete a post by its ID
function deletePost($postID) {
    global $conn;

    // Prepare and execute query to delete the post
    $stmt = $conn->prepare("DELETE FROM Posts WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    $stmt->execute();

    // Redirect back to the post management page after deletion
    header("Location: post-management.php");
    exit();
}

// Function to log activities
function logActivity($postID, $action) {
    global $conn;

    // Get the UserID of the author of the post
    $stmt = $conn->prepare("SELECT UserID FROM Posts WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    $stmt->execute();
    $result = $stmt->get_result();
    $userID = $result->fetch_assoc()['UserID'];
    $stmt->close();

    // Insert the activity log
    $stmt = $conn->prepare("INSERT INTO ActivityLog (UserID, Action, PostID) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $userID, $action, $postID);
    if (!$stmt->execute()) {
        echo "Error logging activity: " . $stmt->error;
    }
    $stmt->close();
}
?>