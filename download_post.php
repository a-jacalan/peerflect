<?php
require_once('pdf_generator.php');
require_once('config.php');

// Check if postID is set
if(!isset($_GET['postID'])) {
    die('Post ID not provided');
}

$postID = $_GET['postID'];

// Fetch questions from database
$stmt = $conn->prepare("SELECT * FROM Questions WHERE PostID = ?");
$stmt->bind_param("i", $postID);
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

// Generate PDF
$pdf = generatePostPDF($questions, $postID);

// Output PDF
$pdf->Output('study_material_post_' . $postID . '.pdf', 'D');
?>