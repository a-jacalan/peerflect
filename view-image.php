<?php
// Check if the image file name is provided as a query parameter
if (isset($_GET['file'])) {
    // Sanitize the file name to prevent directory traversal
    $fileName = basename($_GET['file']);
    
    // Define the path to the images directory
    $imagePath = "application-files/" . $fileName;

    // Check if the file exists
    if (file_exists($imagePath)) {
        // Set the appropriate header for image rendering
        header('Content-Type: image/jpeg'); // Change the content type if your images are in different formats
        
        // Output the image content
        readfile($imagePath);
        exit;
    }
}
// If the file does not exist or is not provided, redirect to an error page or display a message
echo "Image not found";
?>