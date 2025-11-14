<?php
/**
 * edit_step.php
 *
 * Update a step. Requires id.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    if (empty($in['id'])) respond(['error' => 'id is required'], 400);
    $id = (int)$in['id'];

    $fields = ['instruction_text', 'sort_order'];
    $sets = [];
    $params = [':id' => $id];
    foreach ($fields as $f) {
        if (array_key_exists($f, $in)) {
            $sets[] = "$f = :$f";
            $params[":$f"] = $f === 'sort_order' ? (int)$in[$f] : $in[$f];
        }
    }

    if (empty($sets)) respond(['error' => 'No updatable fields provided'], 400);

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id FROM steps WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    if (!$stmt->fetchColumn()) respond(['error' => 'Step not found'], 404);

    $sql = 'UPDATE steps SET ' . implode(', ', $sets) . ' WHERE id = :id';
    $u = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        if (is_int($v)) $u->bindValue($k, $v, PDO::PARAM_INT);
        else $u->bindValue($k, $v);
    }
    $u->execute();

    $ref = $pdo->prepare('SELECT id, recipe_id, instruction_text, sort_order FROM steps WHERE id = :id LIMIT 1');
    $ref->bindValue(':id', $id, PDO::PARAM_INT);
    $ref->execute();
    $row = $ref->fetch(PDO::FETCH_ASSOC);
    respond(['step' => $row], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
