<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set LibreOffice path based on your system
$libreoffice_path = 'C:\Program Files\LibreOffice\program\soffice.exe'; // For Windows

// Create directories
$target_dir = "uploads/";
$output_dir = "output/";
$thumbs_dir = "thumbnails/";

if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
if (!file_exists($output_dir)) mkdir($output_dir, 0777, true);
if (!file_exists($thumbs_dir)) mkdir($thumbs_dir, 0777, true);

if(isset($_FILES['presentation']) && isset($_FILES['thumbnail'])) {
    try {
        // Generate unique ID
        $unique_id = uniqid();
        
        $original_filename = basename($_FILES["presentation"]["name"]);
        $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        $target_file = $target_dir . $unique_id . '.' . $file_extension;
        $fileType = $file_extension;
        
        // Validate presentation file type
        if($fileType != "ppt" && $fileType != "pptx") {
            throw new Exception("Only PPT and PPTX files are allowed.");
        }
        
        // Validate thumbnail file type
        $thumb_filename = basename($_FILES["thumbnail"]["name"]);
        $thumb_extension = strtolower(pathinfo($thumb_filename, PATHINFO_EXTENSION));
        $thumb_file = $thumbs_dir . $unique_id . '.' . $thumb_extension;
        $thumbType = $thumb_extension;
        
        if($thumbType != "jpg" && $thumbType != "jpeg") {
            throw new Exception("Only JPEG thumbnails are allowed.");
        }
        
        // Move uploaded files
        if(!move_uploaded_file($_FILES["presentation"]["tmp_name"], $target_file)) {
            throw new Exception("Failed to move uploaded presentation file.");
        }
        
        if(!move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $thumb_file)) {
            throw new Exception("Failed to move uploaded thumbnail file.");
        }

        // Convert presentation to PDF using LibreOffice
        $pdf_filename = $unique_id . ".pdf";
        $pdf_file = $output_dir . $pdf_filename;

        // Build the command
        $command = '"' . $libreoffice_path . '" --headless --convert-to pdf --outdir "' . 
            realpath($output_dir) . '" "' . realpath($target_file) . '"';

        // Execute the command
        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            throw new Exception("Failed to convert presentation to PDF. Error code: " . $return_var);
        }

        // Rename the output file to match our desired filename
        $temp_pdf = $output_dir . pathinfo($original_filename, PATHINFO_FILENAME) . '.pdf';
        if (file_exists($temp_pdf)) {
            rename($temp_pdf, $pdf_file);
        }

        // Database connection
        $db = new PDO("mysql:host=localhost;dbname=peerflect", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get the title from the form
        $presentation_title = $_POST['title'];

        // Store presentation info in database
        $stmt = $db->prepare("INSERT INTO presentations (
            title,
            original_filename,
            pdf_filename,
            thumb_filename,
            upload_date,
            unique_id
        ) VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $presentation_title,
            $original_filename,
            $pdf_filename,
            $unique_id . '.' . $thumb_extension,
            date('Y-m-d H:i:s'),
            $unique_id
        ]);

        // Redirect to presentations page
        header("Location: presentations.php");
        exit();
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}
?>