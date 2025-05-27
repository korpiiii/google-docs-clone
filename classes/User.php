<?php
class User {
    private $db;
    private $id;
    private $username;
    private $email;
    private $role;
    private $is_active;

    public function __construct($db) {
        $this->db = $db;
    }

    public function load($id) {
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $this->id = $user['id'];
            $this->username = $user['username'];
            $this->email = $user['email'];
            $this->role = $user['role'];
            $this->is_active = $user['is_active'];
            return true;
        }
        return false;
    }

    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
    public function isActive() { return $this->is_active; }

    public static function search($db, $query) {
        $search_term = '%' . $query . '%';
        $stmt = $db->prepare("SELECT id, username FROM users WHERE username LIKE ? OR email LIKE ?");
        $stmt->execute([$search_term, $search_term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAll($db) {
        $stmt = $db->prepare("SELECT * FROM users ORDER BY username");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
