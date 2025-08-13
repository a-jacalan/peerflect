<?php
// Include config file
require_once "config.php";
require_once "check-banned.php";;

// Check if search query is provided
if (isset($_GET['query'])) {
    $search_query = trim($_GET['query']);
    
    // Prepare SQL statement to search posts by title
    $sql = "SELECT PostID, Title, CreatedAt 
            FROM Posts 
            WHERE Title LIKE ? 
            AND IsApproved = 1 
            ORDER BY CreatedAt DESC";
    
    if ($stmt = $conn->prepare($sql)) {
        // Add wildcards to search term
        $search_param = "%" . $search_query . "%";
        $stmt->bind_param("s", $search_param);
        
        // Execute the query
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            // Prepare results array
            $search_results = array();
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $search_results[] = array(
                        'id' => $row['PostID'],
                        'title' => $row['Title'],
                        'created_at' => $row['CreatedAt']
                    );
                }
                // Return results as JSON
                echo json_encode(array('success' => true, 'results' => $search_results));
            } else {
                echo json_encode(array('success' => true, 'results' => array()));
            }
        } else {
            echo json_encode(array('success' => false, 'error' => 'Error executing search'));
        }
        
        $stmt->close();
    } else {
        echo json_encode(array('success' => false, 'error' => 'Error preparing search query'));
    }
    
    $conn->close();
} else {
    echo json_encode(array('success' => false, 'error' => 'No search query provided'));
}
?>