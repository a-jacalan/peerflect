<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to login page
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include database connection
require_once "config.php";

// Handle topic update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $topicID = $_POST["topic_id"];
    $topic_name = trim($_POST["topic_name"]);
    $topic_order = trim($_POST["topic_order"]);

    // Input validation
    $errors = [];

    // Validate topic name
    if (empty($topic_name)) {
        $errors[] = "Please enter a topic name.";
    } elseif (!preg_match("/^[A-Za-z0-9\s]+$/", $topic_name)) {
        $errors[] = "Topic name must contain only letters, numbers, and spaces.";
    }

    // Validate topic order
    if (empty($topic_order) || !is_numeric($topic_order) || $topic_order < 1) {
        $errors[] = "Topic order must be a positive number.";
    }

    // Check if topic name is unique (excluding current topic)
    $sqlCheckTopic = "SELECT topic_id FROM topics WHERE topic_name = ? AND topic_id != ?";
    $stmtCheckTopic = $conn->prepare($sqlCheckTopic);
    $stmtCheckTopic->bind_param("si", $topic_name, $topicID);
    $stmtCheckTopic->execute();
    $resultCheckTopic = $stmtCheckTopic->get_result();
    
    if ($resultCheckTopic->num_rows > 0) {
        $errors[] = "A topic with this name already exists.";
    }

    // If no errors, proceed with updating topic
    if (empty($errors)) {
        $sqlUpdateTopic = "UPDATE topics SET topic_name = ?, topic_order = ? WHERE topic_id = ?";
        $stmtUpdateTopic = $conn->prepare($sqlUpdateTopic);
        $stmtUpdateTopic->bind_param("sii", $topic_name, $topic_order, $topicID);
        
        if ($stmtUpdateTopic->execute()) {
            // Log the update
            $adminID = $_SESSION['id'];
            $action = "Updated Topic";
            $details = "Updated topic with ID: $topicID, New Name: $topic_name, New Order: $topic_order";
            $sqlLogUpdate = "INSERT INTO adminlog (AdminID, Action, Details) VALUES (?, ?, ?)";
            $stmtLogUpdate = $conn->prepare($sqlLogUpdate);
            $stmtLogUpdate->bind_param("iss", $adminID, $action, $details);
            $stmtLogUpdate->execute();

            // Return success response
            echo json_encode(['success' => true, 'message' => 'Topic updated successfully!']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
            exit;
        }
    }

    // If there are errors
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit;