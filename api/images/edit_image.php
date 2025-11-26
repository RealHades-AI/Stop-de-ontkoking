<?php
/**
 * edit_image.php
 *
 * Update image metadata fields. Requires `id`.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    if (empty($in['id'])) respond(['error' => 'id is required'], 400);
    $id = (int)$in['id'];

    $allowed = ['filename','path','mime_type','size_bytes','width','height','hash','variant_type','is_primary','variant_of','owner_type','owner_id'];
    $sets = [];
    $params = [':id' => $id];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $in) && $in[$field] !== null) {
            $sets[] = "$field = :$field";
            $params[":$field"] = $field === 'is_primary' ? (int)(bool)$in[$field] : $in[$field];
        }
    }

    if (empty($sets)) {
        respond(['error' => 'No updatable fields provided'], 400);
    }

    $pdo = get_pdo();
    $pdo->beginTransaction();

    // If owner_type/owner_id not provided but is_primary is being set, fetch owner from existing image
    $ownerType = $in['owner_type'] ?? null;
    $ownerId = isset($in['owner_id']) ? (int)$in['owner_id'] : null;

    if (($in['is_primary'] ?? null) && (empty($ownerType) || empty($ownerId))) {
        $s = $pdo->prepare('SELECT owner_type, owner_id FROM images WHERE id = :id LIMIT 1');
        $s->bindValue(':id', $id, PDO::PARAM_INT);
        $s->execute();
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (empty($ownerType)) $ownerType = $row['owner_type'];
            if (empty($ownerId)) $ownerId = (int)$row['owner_id'];
        }
    }

    // If is_primary true and we have owner info, clear other primary flags
    if (!empty($in['is_primary']) && $ownerType && $ownerId) {
        $clear = $pdo->prepare('UPDATE images SET is_primary = 0 WHERE owner_type = :ot AND owner_id = :oid AND id != :id');
        $clear->bindValue(':ot', $ownerType);
        $clear->bindValue(':oid', $ownerId, PDO::PARAM_INT);
        $clear->bindValue(':id', $id, PDO::PARAM_INT);
        $clear->execute();
    }

    $sql = 'UPDATE images SET ' . implode(', ', $sets) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        if (is_int($v)) $stmt->bindValue($k, $v, PDO::PARAM_INT);
        else $stmt->bindValue($k, $v);
    }
    $stmt->execute();

    $pdo->commit();

    // Return updated row
    $r = $pdo->prepare('SELECT * FROM images WHERE id = :id LIMIT 1');
    $r->bindValue(':id', $id, PDO::PARAM_INT);
    $r->execute();
    $row = $r->fetch(PDO::FETCH_ASSOC);
    respond(['image' => $row], 200);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
