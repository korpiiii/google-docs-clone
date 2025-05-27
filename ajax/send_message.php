<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../classes/User.php';
require_once '../classes/Document.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($input['document_id'], $input['message'])) {
    echo json_encode(['error' => 'Bad Request']);
    exit;
}

$db = (new Database())->getConnection();
$user = new User($db);
$user->load($_SESSION['user_id']);

$document = new Document($db);
if (!$document->load($input['document_id'])) {
    echo json_encode(['error' => 'Document not found']);
    exit;
}

// Check permissions
if ($document->getCreatedBy() != $user->getId() && !isAdmin()) {
    $query = "SELECT id FROM document_permissions WHERE document_id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$document->getId(), $user->getId()]);
    if ($stmt->rowCount() === 0) {
        echo json_encode(['error' => 'No permission to access this document']);
        exit;
    }
}

$message = trim($input['message']);
if (empty($message)) {
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

$query = "INSERT INTO messages (document_id, user_id, message) VALUES (?, ?, ?)";
$stmt = $db->prepare($query);

if ($stmt->execute([$document->getId(), $user->getId(), $message])) {
    ActivityLog::log($db, $document->getId(), $user->getId(), 'chat_message', substr($message, 0, 50));

    $message_id = $db->lastInsertId();
    $query = "SELECT m.*, u.username FROM messages m JOIN users u ON m.user_id = u.id WHERE m.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$message_id]);
    $new_message = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $new_message['id'],
            'username' => $new_message['username'],
            'message' => htmlspecialchars($new_message['message']),
            'created_at' => date('H:i', strtotime($new_message['created_at']))
        ]
    ]);
} else {
    echo json_encode(['error' => 'Failed to send message']);
}
?>
