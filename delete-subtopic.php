<?php
session_start();
require_once "config.php";

// Check if user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subtopicId = $_POST['subtopic_id'];
    $topicId = $_POST['topic_id'];

    // Prepare SQL to delete subtopic
    $sql = "DELETE FROM subtopics WHERE subtopic_id = ? AND topic_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $subtopicId, $topicId);

    if ($stmt->execute()) {
        // Log the deletion action
        $adminID = $_SESSION['id'];
        $action = "Deleted Subtopic";
        $details = "Deleted subtopic ID: $subtopicId, Topic ID: $topicId";
        $logSql = "INSERT INTO adminlog (AdminID, Action, Details) VALUES (?, ?, ?)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("iss", $adminID, $action, $details);
        $logStmt->execute();

        echo json_encode(["success" => true, "message" => "Subtopic deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error deleting subtopic"]);
    }

    $stmt->close();
    $conn->close();
}
?>