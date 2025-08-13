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
    $subtopicName = $_POST['subtopic_name'];
    $subtopicOrder = $_POST['subtopic_order'];

    // Validate input
    if (!preg_match("/^[A-Za-z0-9\s]+$/", $subtopicName)) {
        echo json_encode(["success" => false, "message" => "Invalid subtopic name"]);
        exit;
    }

    // Prepare SQL to update subtopic
    $sql = "UPDATE subtopics SET subtopic_name = ?, subtopic_order = ? WHERE subtopic_id = ? AND topic_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siii", $subtopicName, $subtopicOrder, $subtopicId, $topicId);

    if ($stmt->execute()) {
        // Log the update action
        $adminID = $_SESSION['id'];
        $action = "Updated Subtopic";
        $details = "Updated subtopic ID: $subtopicId, Name: $subtopicName, Order: $subtopicOrder, Topic ID: $topicId";
        $logSql = "INSERT INTO adminlog (AdminID, Action, Details) VALUES (?, ?, ?)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("iss", $adminID, $action, $details);
        $logStmt->execute();

        echo json_encode(["success" => true, "message" => "Subtopic updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error updating subtopic"]);
    }

    $stmt->close();
    $conn->close();
}
?>