<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../classes/Document.php';
require_once '../classes/User.php';
require_once '../classes/ActivityLog.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($input['document_id'], $input['user_id'])) {
    echo json_encode(['error' => 'Bad Request']);
    exit;
}

$db = (new Database())->getConnection();
$current_user = new User($db);
$current_user->load($_SESSION['user_id']);

$document = new Document($db);
if (!$document->load($input['document_id'])) {
    echo json_encode(['error' => 'Document not found']);
    exit;
}

// Verify current user is document owner
if ($document->getCreatedBy() != $current_user->getId() && !isAdmin()) {
    echo json_encode(['error' => 'Only document owner can add collaborators']);
    exit;
}

$collaborator = new User($db);
if (!$collaborator->load($input['user_id'])) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Check if already has access
$query = "SELECT id FROM document_permissions WHERE document_id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$document->getId(), $collaborator->getId()]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['error' => 'User already has access']);
    exit;
}

// Add permission
$query = "INSERT INTO document_permissions (document_id, user_id) VALUES (?, ?)";
$stmt = $db->prepare($query);

if ($stmt->execute([$document->getId(), $collaborator->getId()])) {
    ActivityLog::log($db, $document->getId(), $current_user->getId(),
                   'add_collaborator', "Added {$collaborator->getUsername()}");

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $collaborator->getId(),
            'username' => $collaborator->getUsername()
        ]
    ]);
} else {
    echo json_encode(['error' => 'Failed to add collaborator']);
}
?>
