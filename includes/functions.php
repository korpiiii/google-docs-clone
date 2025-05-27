<?php
require_once 'db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: " . BASE_URL . "/" . $url);
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function logActivity($document_id, $action, $details = '') {
    $db = (new Database())->getConnection();
    $query = "INSERT INTO activity_logs (document_id, user_id, action, details) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$document_id, $_SESSION['user_id'], $action, $details]);
}

function getUserDocuments($user_id) {
    $db = (new Database())->getConnection();

    // Get documents created by user or shared with user
    $query = "SELECT d.* FROM documents d
              LEFT JOIN document_permissions dp ON d.id = dp.document_id
              WHERE d.created_by = ? OR dp.user_id = ?
              GROUP BY d.id
              ORDER BY d.updated_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllDocuments() {
    $db = (new Database())->getConnection();
    $query = "SELECT d.*, u.username as creator FROM documents d
              JOIN users u ON d.created_by = u.id
              ORDER BY d.updated_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function canEditDocument($document_id, $user_id) {
    $db = (new Database())->getConnection();

    // Check if user is creator
    $query = "SELECT created_by FROM documents WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$document_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($document && $document['created_by'] == $user_id) {
        return true;
    }

    // Check if user has edit permission
    $query = "SELECT can_edit FROM document_permissions WHERE document_id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$document_id, $user_id]);
    $permission = $stmt->fetch(PDO::FETCH_ASSOC);

    return $permission && $permission['can_edit'];
}
?>
