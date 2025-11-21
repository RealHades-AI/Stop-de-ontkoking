<?php
/**
 * edit_review.php
 *
 * Update a review's rating/title/text.
 * HTTP POST JSON: { "id":123, "rating":4, "title":"...", "text":"..." }
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

    $updates = [];
    $binds = [];
    if (isset($in['rating'])) {
        $r = (int)$in['rating'];
        if ($r < 1 || $r > 5) respond(['error' => 'rating must be between 1 and 5'], 422);
        $updates[] = 'rating = :rating'; $binds[':rating'] = $r;
    }
    if (isset($in['title'])) { $updates[] = 'title = :title'; $binds[':title'] = trim((string)$in['title']); }
    if (isset($in['text'])) { $updates[] = 'text = :text'; $binds[':text'] = (string)$in['text']; }

    if (empty($updates)) respond(['error' => 'No fields to update'], 422);

    $sql = 'UPDATE recipe_reviews SET ' . implode(', ', $updates) . ' WHERE id = :id';
    $u = $pdo->prepare($sql);
    foreach ($binds as $k => $v) $u->bindValue($k, $v);
    $u->bindValue(':id', $id, PDO::PARAM_INT);
    $u->execute();

    $ref = $pdo->prepare('SELECT id, recipe_id, user_id, rating, title, text, created_at, updated_at FROM recipe_reviews WHERE id = :id LIMIT 1');
    $ref->bindValue(':id', $id, PDO::PARAM_INT);
    $ref->execute();
    $updated = $ref->fetch(PDO::FETCH_ASSOC);
    respond(['review' => $updated], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
