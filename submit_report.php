<?php
session_start();
require_once "config.php";
require_once "check-banned.php";;

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["id"])) {
    echo json_encode(["success" => false, "message" => "Please log in to submit a report"]);
    exit;
}

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

// Validate required parameters
if (!isset($_POST["postID"]) || !isset($_POST["reason"])) {
    echo json_encode(["success" => false, "message" => "Missing required parameters"]);
    exit;
}

try {
    // Sanitize and validate inputs
    $postID = filter_var($_POST["postID"], FILTER_VALIDATE_INT);
    $userID = $_SESSION["id"];
    $reason = trim($_POST["reason"]);
    $reportType = "post";  // Set default report type

    // Validate postID
    if ($postID === false || $postID <= 0) {
        throw new Exception("Invalid post ID");
    }

    // Validate reason length
    if (strlen($reason) < 10) {
        echo json_encode(["success" => false, "message" => "Please provide a more detailed reason (minimum 10 characters)"]);
        exit;
    }

    // Check if post exists
    $checkPost = $conn->prepare("SELECT PostID FROM Posts WHERE PostID = ?");
    $checkPost->bind_param("i", $postID);
    $checkPost->execute();
    if ($checkPost->get_result()->num_rows === 0) {
        throw new Exception("Post not found");
    }
    $checkPost->close();

    // Check if user has already reported this post
    $checkReport = $conn->prepare("SELECT ViolationID FROM Violations WHERE PostID = ? AND UserID = ? AND report_type = 'post'");
    $checkReport->bind_param("ii", $postID, $userID);
    $checkReport->execute();
    if ($checkReport->get_result()->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "You have already reported this post"]);
        exit;
    }
    $checkReport->close();

    // Insert the report
    $stmt = $conn->prepare("INSERT INTO Violations (PostID, UserID, Reason, report_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $postID, $userID, $reason, $reportType);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Report submitted successfully"
        ]);
    } else {
        throw new Exception("Failed to submit report");
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Report submission error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "An error occurred while submitting your report"
    ]);
} finally {
    $conn->close();
}
?>