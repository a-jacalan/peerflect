<?php
session_start();
require_once "config.php";
require_once "check-banned.php";;

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["approve"])) {
        $postID = $_POST["post_id"];
        approvePost($postID);
    } elseif (isset($_POST["reject"])) {
        $postID = $_POST["post_id"];
        rejectPost($postID);
    } elseif (isset($_POST["delete"])) {
        $postID = $_POST["post_id"];
        deletePost($postID);
    }
}

function approvePost($postID) {
    global $conn;
    $stmt = $conn->prepare("UPDATE Posts SET IsApproved = 1 WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    if ($stmt->execute()) {
        logActivity($postID, 'posted');
        $_SESSION['notification'] = array('message' => 'Post approved successfully!', 'type' => 'success');
    } else {
        $_SESSION['notification'] = array('message' => 'Error approving post.', 'type' => 'error');
    }
    header("Location: post-management.php");
    exit();
}

function rejectPost($postID) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM Posts WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    if ($stmt->execute()) {
        $_SESSION['notification'] = array('message' => 'Post rejected and deleted.', 'type' => 'success');
    } else {
        $_SESSION['notification'] = array('message' => 'Error rejecting post.', 'type' => 'error');
    }
    header("Location: post-management.php");
    exit();
}

function deletePost($postID) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM Posts WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    if ($stmt->execute()) {
        $_SESSION['notification'] = array('message' => 'Post deleted successfully.', 'type' => 'success');
    } else {
        $_SESSION['notification'] = array('message' => 'Error deleting post.', 'type' => 'error');
    }
    header("Location: post-management.php");
    exit();
}

function logActivity($postID, $action) {
    global $conn;
    $stmt = $conn->prepare("SELECT UserID FROM Posts WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    $stmt->execute();
    $result = $stmt->get_result();
    $userID = $result->fetch_assoc()['UserID'];
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO ActivityLog (UserID, Action, PostID) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $userID, $action, $postID);
    if (!$stmt->execute()) {
        $_SESSION['notification'] = array('message' => 'Error logging activity: ' . $stmt->error, 'type' => 'error');
    }
    $stmt->close();
}
?>