<?php
session_start();
require_once "config.php";
require_once "check-banned.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION["id"])) {
    $userID = $_SESSION["id"];
    $postID = $_POST["postID"];
    $content = $_POST["content"];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert the comment
        $stmt = $conn->prepare("INSERT INTO comment (UserID, PostID, Content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $userID, $postID, $content);
        
        if ($stmt->execute()) {
            // Log the comment activity
            $logStmt = $conn->prepare("INSERT INTO activitylog (UserID, Action, PostID) VALUES (?, 'commented', ?)");
            $logStmt->bind_param("ii", $userID, $postID);
            $logStmt->execute();

            // Fetch user details for the response
            $userStmt = $conn->prepare("SELECT Username, ProfileImageURL FROM users WHERE UserID = ?");
            $userStmt->bind_param("i", $userID);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $userData = $userResult->fetch_assoc();

            // Commit the transaction
            $conn->commit();

            echo json_encode([
                "success" => true,
                "username" => $userData['Username'],
                "profileImage" => $userData['ProfileImageURL'],
                "content" => $content
            ]);
        } else {
            throw new Exception("Error submitting comment");
        }
    } catch (Exception $e) {
        // Rollback the transaction if any error occurs
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}