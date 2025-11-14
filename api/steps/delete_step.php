<?php
/**
 * delete_step.php
 *
 * Delete a step by id.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    if (empty($in['id'])) respond(['error' => 'id is required'], 400);
    $id = (int)$in['id'];

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id FROM steps WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    if (!$stmt->fetchColumn()) respond(['error' => 'Step not found'], 404);

    $del = $pdo->prepare('DELETE FROM steps WHERE id = :id');
    $del->bindValue(':id', $id, PDO::PARAM_INT);
    $del->execute();

    respond(['deleted' => true, 'id' => $id], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
