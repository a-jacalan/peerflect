<?php
session_start();
require_once "config.php";
require_once "check-banned.php";;

if(isset($_GET['postID'])) {
    $postID = $_GET['postID'];
    
    $stmt = $conn->prepare("SELECT r.Score, u.Username, r.CreatedAt FROM rating r JOIN Users u ON r.UserID = u.UserID WHERE r.PostID = ? ORDER BY r.CreatedAt DESC");
    $stmt->bind_param("i", $postID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ratings = [];
    while($row = $result->fetch_assoc()) {
        $ratings[] = [
            'username' => $row['Username'],
            'score' => $row['Score'],
            'createdAt' => $row['CreatedAt']
        ];
    }
    
    echo json_encode(['success' => true, 'ratings' => $ratings]);
} else {
    echo json_encode(['success' => false, 'message' => 'Post ID not provided']);
}
?>