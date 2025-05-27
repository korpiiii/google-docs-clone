<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_FILES['image'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Bad Request']));
}

$uploadDir = '../assets/images/uploads/';
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Check file type
if (!in_array($_FILES['image']['type'], $allowedTypes)) {
    http_response_code(415);
    die(json_encode(['error' => 'Unsupported media type']));
}

// Check file size
if ($_FILES['image']['size'] > $maxSize) {
    http_response_code(413);
    die(json_encode(['error' => 'File too large']));
}

// Create upload directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
    echo json_encode([
        'success' => 1,
        'file' => [
            'url' => BASE_URL . '/assets/images/uploads/' . $filename
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to upload image']);
}
?>
