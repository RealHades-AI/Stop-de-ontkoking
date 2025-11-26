<?php
/**
 * delete_review.php
 *
 * Delete a review by id.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $id = isset($in['id']) ? (int)$in['id'] : null;
    if (!$id) respond(['error' => 'id is required'], 422);

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id, recipe_id, user_id, rating, title, text FROM recipe_reviews WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(['error' => 'Review not found'], 404);

    $del = $pdo->prepare('DELETE FROM recipe_reviews WHERE id = :id');
    $del->bindValue(':id', $id, PDO::PARAM_INT);
    $del->execute();

    respond(['deleted' => $row], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
