<?php
include_once 'config.php';

$categories = [];

try {
    // Fetch topics
    $stmt = $conn->prepare("SELECT topic_id, topic_name FROM topics ORDER BY topic_order");
    if (!$stmt) {
        throw new Exception("Topic preparation failed: " . $conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Topic execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    while ($topic = $result->fetch_assoc()) {
        $topicName = $topic['topic_name'];
        
        // Fetch subtopics for this topic
        $subtopicStmt = $conn->prepare("SELECT subtopic_name 
                                      FROM subtopics 
                                      WHERE topic_id = ? 
                                      ORDER BY subtopic_order");
        if (!$subtopicStmt) {
            throw new Exception("Subtopic preparation failed: " . $conn->error);
        }
        
        $subtopicStmt->bind_param("i", $topic['topic_id']);
        
        if (!$subtopicStmt->execute()) {
            throw new Exception("Subtopic execution failed: " . $subtopicStmt->error);
        }
        
        $subtopicResult = $subtopicStmt->get_result();
        
        $subtopics = [];
        while ($subtopic = $subtopicResult->fetch_assoc()) {
            $subtopics[] = [
                'name' => $subtopic['subtopic_name']
            ];
        }
        
        $categories[] = [
            'name' => $topicName,
            'subtopics' => $subtopics
        ];
        
        $subtopicStmt->close();
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

header('Content-Type: application/json');
echo json_encode($categories);
?>