<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['document_id'], $_POST['message'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Bad Request']));
}

$document_id = (int)$_POST['document_id'];
$message = sanitize($_POST['message']);

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

// Insert message
$query = "INSERT INTO messages (document_id, user_id, message) VALUES (?, ?, ?)";
$stmt = $db->prepare($query);

if ($stmt->execute([$document_id, $_SESSION['user_id'], $message])) {
    // Log activity
    logActivity($document_id, 'send_message', "Sent a message in chat");

    // Get message details for response
    $message_id = $db->lastInsertId();
    $query = "SELECT m.*, u.username FROM messages m
              JOIN users u ON m.user_id = u.id
              WHERE m.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $message['id'],
            'username' => $message['username'],
            'message' => $message['message'],
            'created_at' => date('H:i', strtotime($message['created_at']))
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message']);
}
?>
