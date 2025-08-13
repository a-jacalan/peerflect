<?php
session_start();
require_once "config.php";

// Check if user is admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["usertype"] !== "admin") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "Invalid ID"]);
    exit;
}

try {
    $db = new PDO("mysql:host=localhost;dbname=peerflect", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get file names before deletion
    $stmt = $db->prepare("SELECT pdf_filename, thumb_filename FROM presentations WHERE id = ?");
    $stmt->execute([$id]);
    $presentation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($presentation) {
        // Delete files
        @unlink("output/" . $presentation['pdf_filename']);
        @unlink("thumbnails/" . $presentation['thumb_filename']);

        // Delete from database
        $stmt = $db->prepare("DELETE FROM presentations WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Presentation not found"]);
    }
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>