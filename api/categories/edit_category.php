<?php
/**
 * edit_category.php
 *
 * Update a category's name or slug.
 * HTTP POST JSON: { "id": 2, "name":"New Name" }
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $pdo = get_pdo();

    $id = isset($in['id']) ? (int)$in['id'] : null;
    if (!$id) respond(['error' => 'id is required'], 422);

    $updates = [];
    if (isset($in['name'])) $updates['name'] = trim((string)$in['name']);
    if (isset($in['slug'])) $updates['slug'] = slugify(trim((string)$in['slug']));

    if (empty($updates)) respond(['error' => 'No fields to update'], 422);

    // If name present and slug not provided, generate slug
    if (isset($updates['name']) && !isset($updates['slug'])) {
        $updates['slug'] = slugify($updates['name']);
    }

    $set = [];
    $binds = [];
    foreach ($updates as $k => $v) {
        $set[] = "$k = :$k";
        $binds[":$k"] = $v;
    }

    $sql = 'UPDATE categories SET ' . implode(', ', $set) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    foreach ($binds as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    try {
        $stmt->execute();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            respond(['error' => 'Duplicate category name/slug', 'detail' => $e->getMessage()], 409);
        }
        throw $e;
    }

    $ref = $pdo->prepare('SELECT id, name, slug FROM categories WHERE id = :id LIMIT 1');
    $ref->bindValue(':id', $id, PDO::PARAM_INT);
    $ref->execute();
    $row = $ref->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(['error' => 'Category not found after update'], 500);
    respond(['category' => $row], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
