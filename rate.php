<?php
// rate.php - handle AJAX rating submissions
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// ensure logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// parse JSON input
$input = json_decode(file_get_contents('php://input'), true);
$movie_id = isset($input['movie_id']) ? (int)$input['movie_id'] : 0;
$rating   = isset($input['rating'])   ? (int)$input['rating']   : 0;

if ($movie_id < 1 || $rating < 1 || $rating > 10) {
    echo json_encode(['success' => false, 'message' => 'Invalid movie or rating']);
    exit;
}

// insert the review
$stmt = $pdo->prepare("INSERT INTO reviews (user_id, movie_id, rating) VALUES (?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $movie_id, $rating]);

// calculate new average
$stmt = $pdo->prepare("SELECT ROUND(AVG(rating),1) AS avg_rating FROM reviews WHERE movie_id = ?");
$stmt->execute([$movie_id]);
$avg = (float)$stmt->fetchColumn();

// return JSON with updated average
echo json_encode(['success' => true, 'avg_rating' => $avg]);
exit;
?>
