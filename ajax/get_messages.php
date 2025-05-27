<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

if (!isset($_GET['document_id'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Bad Request']));
}

$document_id = (int)$_GET['document_id'];

// Verify user has access to this document
$has_access = false;
$db = (new Database())->getConnection();

// Check if user is creator
$query = "SELECT created_by FROM documents WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$document_id]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if ($document && ($document['created_by'] == $_SESSION['user_id'] || isAdmin())) {
    $has_access = true;
}

// Check if user has permission
if (!$has_access) {
    $query = "SELECT id FROM document_permissions WHERE document_id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    $has_access = $stmt->rowCount() > 0;
}

if (!$has_access) {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

// Get messages
$query = "SELECT m.*, u.username FROM messages m
          JOIN users u ON m.user_id = u.id
          WHERE m.document_id = ?
          ORDER BY m.created_at ASC";
$stmt = $db->prepare($query);
$stmt->execute([$document_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format messages for response
$formatted_messages = array_map(function($message) {
    return [
        'id' => $message['id'],
        'username' => $message['username'],
        'message' => $message['message'],
        'created_at' => date('H:i', strtotime($message['created_at']))
    ];
}, $messages);

echo json_encode($formatted_messages);
?>
