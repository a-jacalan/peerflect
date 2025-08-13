<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to login page
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config.php";

// Handle topic addition
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
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

    // Check if topic already exists
    $sqlCheckTopic = "SELECT topic_id FROM topics WHERE topic_name = ?";
    $stmtCheckTopic = $conn->prepare($sqlCheckTopic);
    $stmtCheckTopic->bind_param("s", $topic_name);
    $stmtCheckTopic->execute();
    $resultCheckTopic = $stmtCheckTopic->get_result();
    
    if ($resultCheckTopic->num_rows > 0) {
        $errors[] = "A topic with this name already exists.";
    }

    // If no errors, proceed with adding topic
    if (empty($errors)) {
        $sqlAddTopic = "INSERT INTO topics (topic_name, topic_order) VALUES (?, ?)";
        $stmtAddTopic = $conn->prepare($sqlAddTopic);
        $stmtAddTopic->bind_param("si", $topic_name, $topic_order);
        
        if ($stmtAddTopic->execute()) {
            // Log the addition
            $adminID = $_SESSION['id'];
            $action = "Added Topic";
            $details = "Added new topic with Name: $topic_name, Order: $topic_order";
            $sqlLogAddition = "INSERT INTO adminlog (AdminID, Action, Details) VALUES (?, ?, ?)";
            $stmtLogAddition = $conn->prepare($sqlLogAddition);
            $stmtLogAddition->bind_param("iss", $adminID, $action, $details);
            $stmtLogAddition->execute();

            // Redirect with success message
            $_SESSION['message'] = "Topic added successfully!";
            header("location: topics.php");
            exit;
        } else {
            $errors[] = "Something went wrong. Please try again later.";
        }
    }

    // If there are errors, store them in session to display on redirect
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("location: topics.php");
        exit;
    }
}