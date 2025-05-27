<?php
class ActivityLog {
    private $db;
    private $id;
    private $document_id;
    private $user_id;
    private $action;
    private $details;
    private $created_at;

    public function __construct($db) {
        $this->db = $db;
    }

    public static function log($db, $document_id, $user_id, $action, $details = '') {
        $query = "INSERT INTO activity_logs (document_id, user_id, action, details) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        return $stmt->execute([$document_id, $user_id, $action, $details]);
    }

    public static function getForDocument($db, $document_id) {
        $query = "SELECT al.*, u.username FROM activity_logs al
                  JOIN users u ON al.user_id = u.id
                  WHERE al.document_id = ?
                  ORDER BY al.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$document_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
