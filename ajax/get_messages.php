<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../classes/Document.php';
require_once '../classes/User.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['document_id'])) {
    echo json_encode(['error' => 'Document ID required']);
    exit;
}

$db = (new Database())->getConnection();
$document_id = (int)$_GET['document_id'];
$user_id = $_SESSION['user_id'];

// Check permissions
$document = new Document($db);
if (!$document->load($document_id)) {
    echo json_encode(['error' => 'Document not found']);
    exit;
}

if ($document->getCreatedBy() != $user_id && !isAdmin()) {
    $query = "SELECT id FROM document_permissions WHERE document_id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$document_id, $user_id]);
    if ($stmt->rowCount() === 0) {
        echo json_encode(['error' => 'No permission to access this document']);
        exit;
    }
}

$query = "SELECT m.*, u.username FROM messages m
          JOIN users u ON m.user_id = u.id
          WHERE m.document_id = ?
          ORDER BY m.created_at ASC";
$stmt = $db->prepare($query);
$stmt->execute([$document_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$formatted_messages = array_map(function($message) {
    return [
        'id' => $message['id'],
        'username' => $message['username'],
        'message' => htmlspecialchars($message['message']),
        'created_at' => date('H:i', strtotime($message['created_at']))
    ];
}, $messages);

echo json_encode($formatted_messages);
?>
