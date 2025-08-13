<?php
session_start();

// Include database connection
require_once "config.php";
require_once "check-banned.php";

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['error' => 'Please login to submit a suggestion']);
    exit;
}

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Validate and sanitize inputs
$title = trim($_POST["title"]);
$description = trim($_POST["description"]);

// Validate required fields
if (empty($title) || empty($description)) {
    echo json_encode(['error' => 'Please fill all required fields']);
    exit;
}

// Additional validation
if (strlen($title) > 255) { // Assuming VARCHAR(255) in database
    echo json_encode(['error' => 'Title is too long (maximum 255 characters)']);
    exit;
}

if (strlen($description) > 1000) { // Add appropriate length limit
    echo json_encode(['error' => 'Description is too long (maximum 1000 characters)']);
    exit;
}

try {
    // Insert the new suggestion
    $sql = "INSERT INTO TopicSuggestions (UserID, Title, Description, Status, CreatedAt) 
            VALUES (?, ?, ?, 'PENDING', NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $_SESSION["id"], $title, $description);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to submit suggestion']);
    }
} catch (Exception $e) {
    // Log error internally (don't expose detailed error to user)
    error_log("Error in add-suggestion.php: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred while processing your request']);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>