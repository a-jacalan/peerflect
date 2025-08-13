<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to login page
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config.php";

// Check if topic ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: topics.php");
    exit;
}

$topicID = $_GET['id'];

// Fetch current topic details
$sqlGetTopic = "SELECT topic_id, topic_name, topic_order FROM topics WHERE topic_id = ?";
$stmtGetTopic = $conn->prepare($sqlGetTopic);
$stmtGetTopic->bind_param("i", $topicID);
$stmtGetTopic->execute();
$resultTopic = $stmtGetTopic->get_result();

// Check if topic exists
if ($resultTopic->num_rows == 0) {
    header("location: topics.php");
    exit;
}

$topic = $resultTopic->fetch_assoc();

// Handle topic update
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

            // Redirect with success message
            $_SESSION['message'] = "Topic updated successfully!";
            header("location: topics.php");
            exit;
        } else {
            $errors[] = "Something went wrong. Please try again later.";
        }
    }

    // If there are errors, store them in session to display on redirect
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Topic - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="topics-bg">
    <div class="container">
        <div class="edit-topic-form">
            <h2>Edit Topic</h2>
            
            <?php
            // Display any errors
            if (isset($_SESSION['errors'])) {
                echo '<div class="error-container">';
                foreach ($_SESSION['errors'] as $error) {
                    echo '<p class="error-message">' . htmlspecialchars($error) . '</p>';
                }
                echo '</div>';
                unset($_SESSION['errors']);
            }
            ?>

            <form action="" method="post">
                <div class="form-group">
                    <label for="topic_name">Topic Name:</label>
                    <input type="text" id="topic_name" name="topic_name" 
                           value="<?php echo htmlspecialchars($topic['topic_name']); ?>" 
                           required pattern="[A-Za-z0-9\s]+" 
                           title="Only letters, numbers, and spaces allowed">
                </div>

                <div class="form-group">
                    <label for="topic_order">Topic Order:</label>
                    <input type="number" id="topic_order" name="topic_order" 
                           value="<?php echo htmlspecialchars($topic['topic_order']); ?>" 
                           required min="1">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Topic</button>
                    <a href="topics.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>