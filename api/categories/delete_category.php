<?php
/**
 * delete_category.php
 *
 * Delete a category by id or name. Note: recipes.category_id uses ON DELETE SET NULL per migrations.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $pdo = get_pdo();

    $identOk = false;
    $params = [];
    $where = [];
    if (isset($in['id'])) { $where[] = 'id = :id'; $params[':id'] = (int)$in['id']; $identOk = true; }
    if (isset($in['name'])) { $where[] = 'name = :name'; $params[':name'] = $in['name']; $identOk = true; }
    if (isset($in['slug'])) { $where[] = 'slug = :slug'; $params[':slug'] = $in['slug']; $identOk = true; }
    if (!$identOk) respond(['error' => 'Provide id or name or slug to delete'], 422);

    $sql = 'SELECT id, name, slug FROM categories WHERE ' . implode(' OR ', $where) . ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(['error' => 'Category not found'], 404);

    $del = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $del->bindValue(':id', (int)$row['id'], PDO::PARAM_INT);
    $del->execute();

    respond(['deleted' => $row], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
