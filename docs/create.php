<?php
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $content = $_POST['content']; // Don't sanitize here to preserve HTML

    if (empty($title)) {
        $_SESSION['error'] = 'Document title is required.';
    } else {
        $db = (new Database())->getConnection();
        $query = "INSERT INTO documents (title, content, created_by) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);

        if ($stmt->execute([$title, $content, $_SESSION['user_id']])) {
            $document_id = $db->lastInsertId();

            // Log activity
            logActivity($document_id, 'create_document', "Document '$title' created");

            $_SESSION['success'] = 'Document created successfully.';
            redirect("edit.php?id=$document_id");
        } else {
            $_SESSION['error'] = 'Something went wrong. Please try again.';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <h4>Create New Document</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form method="POST" id="document-form">
                    <div class="mb-3">
                        <label for="title" class="form-label">Document Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <div id="editor" style="height: 500px; border: 1px solid #ddd; padding: 10px;"></div>
                        <textarea id="content" name="content" style="display: none;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Document</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/editor.js"></script>
<script>
    document.getElementById('document-form').addEventListener('submit', function() {
        document.getElementById('content').value = editor.getHtml();
    });
</script>

<?php include '../includes/footer.php'; ?>
