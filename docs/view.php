<?php
require_once '../includes/header.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$document_id = $_GET['id'];
$db = (new Database())->getConnection();

// Get document details
$query = "SELECT d.*, u.username as creator FROM documents d
          JOIN users u ON d.created_by = u.id
          WHERE d.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$document_id]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    $_SESSION['error'] = 'Document not found.';
    redirect('index.php');
}

// Check if user has permission to view (creator or has permission)
$has_permission = false;
if ($document['created_by'] == $_SESSION['user_id']) {
    $has_permission = true;
} else {
    $query = "SELECT id FROM document_permissions WHERE document_id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    $has_permission = $stmt->rowCount() > 0;
}

if (!$has_permission && !isAdmin()) {
    $_SESSION['error'] = 'You do not have permission to view this document.';
    redirect('index.php');
}

// Get activity logs
$query = "SELECT al.*, u.username FROM activity_logs al
          JOIN users u ON al.user_id = u.id
          WHERE al.document_id = ?
          ORDER BY al.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$document_id]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get messages
$query = "SELECT m.*, u.username FROM messages m
          JOIN users u ON m.user_id = u.id
          WHERE m.document_id = ?
          ORDER BY m.created_at ASC";
$stmt = $db->prepare
