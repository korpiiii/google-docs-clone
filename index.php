
<?php
require_once 'includes/header.php';

$db = (new Database())->getConnection();

if (isAdmin()) {
    $documents = getAllDocuments();
} else {
    $documents = getUserDocuments($_SESSION['user_id']);
}
?>

<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/paragraph@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/image@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
<script src="editor.js"></script>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>My Documents</h4>
                <a href="docs/create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Document
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($documents)): ?>
                    <div class="alert alert-info">No documents found. Create your first document!</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Owner</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td><?php echo $doc['title']; ?></td>
                                        <td>
                                            <?php if (isset($doc['creator'])): ?>
                                                <?php echo $doc['creator']; ?>
                                            <?php else: ?>
                                                <?php echo $_SESSION['username']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($doc['updated_at'])); ?></td>
                                        <td>
                                            <a href="docs/<?php echo ($doc['created_by'] == $_SESSION['user_id'] || canEditDocument($doc['id'], $_SESSION['user_id'])) ? 'edit' : 'view'; ?>.php?id=<?php echo $doc['id']; ?>"
                                               class="btn btn-sm btn-primary">
                                                <?php echo ($doc['created_by'] == $_SESSION['user_id'] || canEditDocument($doc['id'], $_SESSION['user_id'])) ? 'Edit' : 'View'; ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
