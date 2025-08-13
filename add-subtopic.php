<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to login page
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include database connection
require_once "config.php";

// Handle subtopic addition
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $topicID = $_POST["topic_id"];
    $subtopic_name = trim($_POST["subtopic_name"]);
    $subtopic_order = trim($_POST["subtopic_order"]);

    // Input validation
    $errors = [];

    // Validate subtopic name
    if (empty($subtopic_name)) {
        $errors[] = "Please enter a subtopic name.";
    } elseif (!preg_match("/^[A-Za-z0-9\s]+$/", $subtopic_name)) {
        $errors[] = "Subtopic name must contain only letters, numbers, and spaces.";
    }

    // Validate subtopic order
    if (empty($subtopic_order) || !is_numeric($subtopic_order) || $subtopic_order < 1) {
        $errors[] = "Subtopic order must be a positive number.";
    }

    // Check if subtopic name is unique within this topic
    $sqlCheckSubtopic = "SELECT subtopic_id FROM subtopics WHERE subtopic_name = ? AND topic_id = ?";
    $stmtCheckSubtopic = $conn->prepare($sqlCheckSubtopic);
    $stmtCheckSubtopic->bind_param("si", $subtopic_name, $topicID);
    $stmtCheckSubtopic->execute();
    $resultCheckSubtopic = $stmtCheckSubtopic->get_result();
    
    if ($resultCheckSubtopic->num_rows > 0) {
        $errors[] = "A subtopic with this name already exists in this topic.";
    }

    // Verify the topic exists
    $sqlCheckTopic = "SELECT topic_id FROM topics WHERE topic_id = ?";
    $stmtCheckTopic = $conn->prepare($sqlCheckTopic);
    $stmtCheckTopic->bind_param("i", $topicID);
    $stmtCheckTopic->execute();
    $resultCheckTopic = $stmtCheckTopic->get_result();
    
    if ($resultCheckTopic->num_rows == 0) {
        $errors[] = "The specified topic does not exist.";
    }

    // If no errors, proceed with adding subtopic
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();

            // Insert subtopic
            $sqlAddSubtopic = "INSERT INTO subtopics (topic_id, subtopic_name, subtopic_order) VALUES (?, ?, ?)";
            $stmtAddSubtopic = $conn->prepare($sqlAddSubtopic);
            $stmtAddSubtopic->bind_param("isi", $topicID, $subtopic_name, $subtopic_order);
            
            if ($stmtAddSubtopic->execute()) {
                // Get the ID of the newly inserted subtopic
                $newSubtopicId = $conn->insert_id;

                // Log the addition
                $adminID = $_SESSION['id'];
                $action = "Added Subtopic";
                $details = "Added new subtopic to topic ID $topicID: Name: $subtopic_name, Order: $subtopic_order, Subtopic ID: $newSubtopicId";
                $sqlLogAddition = "INSERT INTO adminlog (AdminID, Action, Details) VALUES (?, ?, ?)";
                $stmtLogAddition = $conn->prepare($sqlLogAddition);
                $stmtLogAddition->bind_param("iss", $adminID, $action, $details);
                
                if ($stmtLogAddition->execute()) {
                    // Commit transaction
                    $conn->commit();

                    // Return success response with new subtopic details
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Subtopic added successfully!',
                        'subtopic' => [
                            'id' => $newSubtopicId,
                            'name' => $subtopic_name,
                            'order' => $subtopic_order
                        ]
                    ]);
                    exit;
                } else {
                    // Rollback if logging fails
                    $conn->rollback();
                    throw new Exception("Failed to log subtopic addition");
                }
            } else {
                // Rollback if subtopic insertion fails
                $conn->rollback();
                throw new Exception("Failed to insert subtopic");
            }
        } catch (Exception $e) {
            // Rollback any partial transactions
            $conn->rollback();

            // Log the error
            error_log("Subtopic Addition Error: " . $e->getMessage());

            // Return error response
            echo json_encode([
                'success' => false, 
                'message' => 'An unexpected error occurred. Please try again.'
            ]);
            exit;
        }
    }

    // If there are validation errors
    echo json_encode([
        'success' => false, 
        'message' => implode(', ', $errors)
    ]);
    exit;
}

// If request method is not POST
echo json_encode([
    'success' => false, 
    'message' => 'Invalid request method'
]);
exit;