<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

if (!isset($_GET['q']) || strlen($_GET['q']) < 2) {
    die(json_encode([]));
}

$search_term = '%' . $_GET['q'] . '%';
$current_user_id = $_SESSION['user_id'];

$db = (new Database())->getConnection();
$query = "SELECT id, username FROM users
          WHERE (username LIKE ? OR email LIKE ?)
          AND id != ?
          AND is_active = TRUE
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$search_term, $search_term, $current_user_id]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results);
?>
