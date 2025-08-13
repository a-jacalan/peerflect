<?php
session_start();
require_once "config.php";
require_once "check-banned.php";

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rating']) && isset($_POST['postID'])) {
    $userID = $_SESSION['id'];
    $postID = intval($_POST['postID']);
    $newScore = intval($_POST['rating']);
    
    if ($newScore >= 1 && $newScore <= 5) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Check if the user has already rated this post
            $stmt = $conn->prepare("SELECT Score FROM rating WHERE UserID = ? AND PostID = ?");
            $stmt->bind_param("ii", $userID, $postID);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingRating = $result->fetch_assoc();

            if ($existingRating) {
                // User has already rated, calculate the point difference
                $oldScore = $existingRating['Score'];
                $pointDifference = $newScore - $oldScore;

                // Update the existing rating
                $stmt = $conn->prepare("UPDATE rating SET Score = ? WHERE UserID = ? AND PostID = ?");
                $stmt->bind_param("iii", $newScore, $userID, $postID);
                $action = 'updated_rating';
            } else {
                // This is a new rating
                $stmt = $conn->prepare("INSERT INTO rating (UserID, PostID, Score) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $userID, $postID, $newScore);
                $pointDifference = $newScore; // For new ratings, add the full score
                $action = 'rated';
            }

            $stmt->execute();

            // Log the rating activity
            $logStmt = $conn->prepare("INSERT INTO activitylog (UserID, Action, PostID) VALUES (?, ?, ?)");
            $logStmt->bind_param("isi", $userID, $action, $postID);
            $logStmt->execute();

            // Update contributor's points and claimable points
            updateContributorPoints($conn, $postID, $pointDifference);

            // If we get here, it means all operations were successful
            $conn->commit();
            
            $avgRating = getAverageRating($conn, $postID);
            echo json_encode([
                'success' => true, 
                'avgRating' => $avgRating['avg'], 
                'count' => $avgRating['count']
            ]);
        } catch (Exception $e) {
            // An error occurred, rollback the transaction
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

function getAverageRating($conn, $postID) {
    if (!$postID) {
        return ['avg' => 0, 'count' => 0];
    }

    $sql = "SELECT AVG(Score) as avg_score, COUNT(*) as count FROM rating WHERE PostID = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return ['avg' => 0, 'count' => 0];
    }

    $stmt->bind_param("i", $postID);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return ['avg' => 0, 'count' => 0];
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return [
        'avg' => $row['avg_score'] ? round($row['avg_score'], 1) : 0, 
        'count' => $row['count'] ?? 0
    ];
}

function updateContributorPoints($conn, $postID, $pointDifference) {
    // Get the UserID of the post creator
    $stmt = $conn->prepare("SELECT UserID FROM Posts WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $contributorID = $row['UserID'];

    // Update both regular points and claimable points for the contributor
    $stmt = $conn->prepare("UPDATE Users SET 
        points = points + ?,
        claimable_points = claimable_points + ?
        WHERE UserID = ?");
    $stmt->bind_param("iii", $pointDifference, $pointDifference, $contributorID);
    $stmt->execute();
}