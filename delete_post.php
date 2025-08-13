<?php
// Create a new file named delete_post.php
session_start();
require_once "config.php";
require_once "check-banned.php";;

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to perform this action.']);
    exit;
}

// Check if postID is provided
if (!isset($_POST['postID'])) {
    echo json_encode(['success' => false, 'message' => 'Post ID is required.']);
    exit;
}

$postID = $_POST['postID'];
$userID = $_SESSION['id'];

// Check user permissions
$stmt = $conn->prepare("SELECT u.usertype, u.school, p.UserID as PostUserID, author.school as AuthorSchool 
                       FROM Users u 
                       JOIN Posts p ON p.PostID = ? 
                       JOIN Users author ON author.UserID = p.UserID 
                       WHERE u.UserID = ?");
$stmt->bind_param("ii", $postID, $userID);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Check if user has permission to delete
$hasPermission = false;

// User is the post author
if ($userData['PostUserID'] == $userID) {
    $hasPermission = true;
}
// User is admin
else if ($userData['usertype'] == 'admin') {
    $hasPermission = true;
}
// User is school admin and post is from their school
else if ($userData['usertype'] == 'schooladmin' && $userData['school'] == $userData['AuthorSchool']) {
    $hasPermission = true;
}

if (!$hasPermission) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this post.']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete related records first
    // Delete comments
    $stmt = $conn->prepare("DELETE FROM Comment WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    $stmt->execute();

    // Delete ratings
    $stmt = $conn->prepare("DELETE FROM rating WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    $stmt->execute();

    // Delete questions
    $stmt = $conn->prepare("DELETE FROM Questions WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    $stmt->execute();

    // Finally, delete the post
    $stmt = $conn->prepare("DELETE FROM Posts WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the post.']);
}

$conn->close();
?>