<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['document_id'], $_POST['user_id'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Bad Request']));
}

$document_id = (int)$_POST['document_id'];
$user_id = (int)$_POST['user_id'];

// Verify current user is the document owner
$db = (new Database())->getConnection();
$query = "SELECT created_by FROM documents WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$document_id]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document || $document['created_by'] != $_SESSION['user_id']) {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

// Get user details before removal for logging
$query = "SELECT username FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Remove permission
$query = "DELETE FROM document_permissions WHERE document_id = ? AND user_id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute([$document_id, $user_id])) {
    // Log activity
    logActivity($document_id, 'remove_collaborator', "Removed {$user['username']} as collaborator");

    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to remove user']);
}
?>
