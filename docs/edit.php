<?php
require_once '../includes/header.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$document_id = $_GET['id'];
$db = (new Database())->getConnection();

// Get document details
$query = "SELECT * FROM documents WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$document_id]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    $_SESSION['error'] = 'Document not found.';
    redirect('index.php');
}

// Check permissions
if (!canEditDocument($document_id, $_SESSION['user_id'])) {
    $_SESSION['error'] = 'You do not have permission to edit this document.';
    redirect('index.php');
}

// Handle auto-save via AJAX, so no form submission here

// Get document permissions
$query = "SELECT u.id, u.username FROM document_permissions dp
          JOIN users u ON dp.user_id = u.id
          WHERE dp.document_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$document_id]);
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
$stmt = $db->prepare($query);
$stmt->execute([$document_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Editing: <?php echo $document['title']; ?></h4>
                <div>
                    <span class="badge bg-success" id="save-status">Saved</span>
                </div>
            </div>
            <div class="card-body">
                <div id="editor" style="height: 600px; border: 1px solid #ddd; padding: 10px;"><?php echo $document['content']; ?></div>
                <textarea id="content" style="display: none;"><?php echo $document['content']; ?></textarea>
                <input type="hidden" id="document_id" value="<?php echo $document_id; ?>">
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Collaborators</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Add Collaborator</label>
                    <input type="text" class="form-control" id="search-user" placeholder="Search users...">
                    <div id="search-results" class="mt-2"></div>
                </div>
                <h6>Current Collaborators:</h6>
                <ul class="list-group" id="collaborators-list">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo $_SESSION['username']; ?> (Owner)
                    </li>
                    <?php foreach ($permissions as $permission): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo $permission['username']; ?>
                            <button class="btn btn-sm btn-danger remove-collaborator" data-user-id="<?php echo $permission['id']; ?>">
                                <i class="fas fa-times"></i>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Activity Log</h5>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <ul class="list-group">
                    <?php foreach ($activities as $activity): ?>
                        <li class="list-group-item">
                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></small><br>
                            <strong><?php echo $activity['username']; ?></strong>: <?php echo $activity['action']; ?>
                            <?php if (!empty($activity['details'])): ?>
                                <br><small><?php echo $activity['details']; ?></small>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Chat</h5>
            </div>
            <div class="card-body">
                <div id="chat-messages" style="max-height: 200px; overflow-y: auto; margin-bottom: 10px;">
                    <?php foreach ($messages as $message): ?>
                        <div class="mb-2">
                            <strong><?php echo $message['username']; ?></strong>
                            <small class="text-muted"><?php echo date('H:i', strtotime($message['created_at'])); ?></small><br>
                            <?php echo $message['message']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="input-group">
                    <input type="text" class="form-control" id="chat-message" placeholder="Type your message...">
                    <button class="btn btn-primary" id="send-message">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/editor.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/document.js"></script>

<?php include '../includes/footer.php'; ?>
