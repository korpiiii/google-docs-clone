<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['document_id'], $_POST['content'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Bad Request']));
}

$document_id = (int)$_POST['document_id'];
$content = $_POST['content'];

// Verify user has permission to edit this document
if (!canEditDocument($document_id, $_SESSION['user_id'])) {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

$db = (new Database())->getConnection();
$query = "UPDATE documents SET content = ?, updated_at = NOW() WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute([$content, $document_id])) {
    // Log activity
    logActivity($document_id, 'auto_save', 'Document auto-saved');

    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error'
