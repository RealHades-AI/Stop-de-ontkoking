<?php
/**
 * add_image.php
 *
 * Add an image metadata row. (This does not handle file uploads.)
 * HTTP POST JSON: { "owner_type":"recipe", "owner_id":123, "path":"/img/...", "filename":"img.jpg", "mime_type":"image/jpeg", "size_bytes":12345, "width":800, "height":600, "is_primary":1 }
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $owner_type = isset($in['owner_type']) ? trim((string)$in['owner_type']) : null;
    $owner_id = isset($in['owner_id']) ? (int)$in['owner_id'] : null;
    $path = isset($in['path']) ? trim((string)$in['path']) : null;

    if (!$owner_type || !$owner_id || !$path) respond(['error' => 'owner_type, owner_id and path are required'], 422);

    $pdo = get_pdo();

    // Optionally: ensure owner exists for common types
    if ($owner_type === 'recipe') {
        $r = find_recipe_by_id($pdo, $owner_id);
        if (!$r) respond(['error' => 'Recipe owner not found', 'field' => 'owner_id'], 422);
    } elseif ($owner_type === 'user') {
        $u = find_user_by_identifier($pdo, ['id' => $owner_id]);
        if (!$u) respond(['error' => 'User owner not found', 'field' => 'owner_id'], 422);
    }

    $stmt = $pdo->prepare('INSERT INTO images (owner_type, owner_id, path, filename, mime_type, size_bytes, width, height, hash, variant_type, is_primary, variant_of) VALUES (:owner_type, :owner_id, :path, :filename, :mime_type, :size_bytes, :width, :height, :hash, :variant_type, :is_primary, :variant_of)');
    $stmt->bindValue(':owner_type', $owner_type, PDO::PARAM_STR);
    $stmt->bindValue(':owner_id', $owner_id, PDO::PARAM_INT);
    $stmt->bindValue(':path', $path, PDO::PARAM_STR);
    $stmt->bindValue(':filename', $in['filename'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':mime_type', $in['mime_type'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':size_bytes', $in['size_bytes'] ?? null, PDO::PARAM_INT);
    $stmt->bindValue(':width', $in['width'] ?? null, PDO::PARAM_INT);
    $stmt->bindValue(':height', $in['height'] ?? null, PDO::PARAM_INT);
    $stmt->bindValue(':hash', $in['hash'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':variant_type', $in['variant_type'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':is_primary', isset($in['is_primary']) ? (int)$in['is_primary'] : 0, PDO::PARAM_INT);
    $stmt->bindValue(':variant_of', $in['variant_of'] ?? null, PDO::PARAM_INT);
    $stmt->execute();
    $id = (int)$pdo->lastInsertId();

    $ref = $pdo->prepare('SELECT * FROM images WHERE id = :id LIMIT 1');
    $ref->bindValue(':id', $id, PDO::PARAM_INT);
    $ref->execute();
    $row = $ref->fetch(PDO::FETCH_ASSOC);
    respond(['image' => $row], 201);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
