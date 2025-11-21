<?php
/**
 * delete_image.php
 *
 * Delete an image by id.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    if (empty($in['id'])) respond(['error' => 'id is required'], 400);
    $id = (int)$in['id'];

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM images WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(['error' => 'Image not found'], 404);

    $del = $pdo->prepare('DELETE FROM images WHERE id = :id');
    $del->bindValue(':id', $id, PDO::PARAM_INT);
    $del->execute();

    respond(['deleted' => true, 'id' => $id], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
