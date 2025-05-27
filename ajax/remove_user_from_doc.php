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
    echo json_encode(['error' => 'Only document owner can remove collaborators']);
    exit;
}

$collaborator = new User($db);
if (!$collaborator->load($input['user_id'])) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Remove permission
$query = "DELETE FROM document_permissions WHERE document_id = ? AND user_id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute([$document->getId(), $collaborator->getId()])) {
    ActivityLog::log($db, $document->getId(), $current_user->getId(),
                   'remove_collaborator', "Removed {$collaborator->getUsername()}");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to remove collaborator']);
}
?>
