<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$uploadDir = 'uploads/';

if (isset($_GET['slide'])) {
    $slideNumber = intval($_GET['slide']);
    $slidePath = $uploadDir . 'slide_' . $slideNumber . '.png';

    if (file_exists($slidePath)) {
        $response = [
            'success' => true,
            'slide_path' => $slidePath
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Slide not found'
        ];
    }

    echo json_encode($response);
}
?>