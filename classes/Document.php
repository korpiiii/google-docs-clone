<?php
class Document {
    private $db;
    private $id;
    private $title;
    private $content;
    private $created_by;
    private $created_at;
    private $updated_at;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($title, $content, $created_by) {
        $query = "INSERT INTO documents (title, content, created_by) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        if ($stmt->execute([$title, $content, $created_by])) {
            $this->id = $this->db->lastInsertId();
            return $this->load($this->id);
        }
        return false;
    }

    public function load($id) {
        $query = "SELECT * FROM documents WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($doc) {
            $this->id = $doc['id'];
            $this->title = $doc['title'];
            $this->content = $doc['content'];
            $this->created_by = $doc['created_by'];
            $this->created_at = $doc['created_at'];
            $this->updated_at = $doc['updated_at'];
            return true;
        }
        return false;
    }

    public function update($content) {
        $query = "UPDATE documents SET content = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$content, $this->id]);
    }

    public function getId() { return $this->id; }
    public function getTitle() { return $this->title; }
    public function getContent() { return $this->content; }
    public function getCreatedBy() { return $this->created_by; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }

    public static function getUserDocuments($db, $user_id) {
        $query = "SELECT d.* FROM documents d
                  LEFT JOIN document_permissions dp ON d.id = dp.document_id
                  WHERE d.created_by = ? OR dp.user_id = ?
                  GROUP BY d.id
                  ORDER BY d.updated_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllDocuments($db) {
        $query = "SELECT d.*, u.username as creator FROM documents d
                  JOIN users u ON d.created_by = u.id
                  ORDER BY d.updated_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
