<?php
/**
 * edit_recipe_category.php
 *
 * Update is_primary or sort_order for a recipe-category association.
 * POST JSON: { "recipe_id":1, "category_id":2, "is_primary":true, "sort_order": 1 }
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $recipe_id = isset($in['recipe_id']) ? (int)$in['recipe_id'] : null;
    $category_id = isset($in['category_id']) ? (int)$in['category_id'] : null;
    if (empty($recipe_id) || empty($category_id)) respond(['error' => 'recipe_id and category_id are required'], 400);

    $updates = [];
    $params = [':rid' => $recipe_id, ':cid' => $category_id];
    if (array_key_exists('is_primary', $in)) { $updates[] = 'is_primary = :is_primary'; $params[':is_primary'] = !empty($in['is_primary']) ? 1 : 0; }
    if (array_key_exists('sort_order', $in)) { $updates[] = 'sort_order = :sort_order'; $params[':sort_order'] = (int)$in['sort_order']; }

    if (empty($updates)) respond(['error' => 'No fields to update'], 400);

    $pdo = get_pdo();
    $pdo->beginTransaction();

    // Ensure association exists
    $check = $pdo->prepare('SELECT 1 FROM recipe_categories WHERE recipe_id = :rid AND category_id = :cid LIMIT 1');
    $check->bindValue(':rid', $recipe_id, PDO::PARAM_INT);
    $check->bindValue(':cid', $category_id, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetchColumn()) {
        $pdo->rollBack();
        respond(['error' => 'Association not found'], 404);
    }

    if (array_key_exists('is_primary', $in) && !empty($in['is_primary'])) {
        // Clear other primary flags for this recipe
        $clear = $pdo->prepare('UPDATE recipe_categories SET is_primary = 0 WHERE recipe_id = :rid AND category_id != :cid');
        $clear->bindValue(':rid', $recipe_id, PDO::PARAM_INT);
        $clear->bindValue(':cid', $category_id, PDO::PARAM_INT);
        $clear->execute();
    }

    $sql = 'UPDATE recipe_categories SET ' . implode(', ', $updates) . ' WHERE recipe_id = :rid AND category_id = :cid';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        if (is_int($v)) $stmt->bindValue($k, $v, PDO::PARAM_INT);
        else $stmt->bindValue($k, $v);
    }
    $stmt->execute();

    $pdo->commit();

    $ref = $pdo->prepare('SELECT recipe_id, category_id, is_primary, sort_order FROM recipe_categories WHERE recipe_id = :rid AND category_id = :cid LIMIT 1');
    $ref->bindValue(':rid', $recipe_id, PDO::PARAM_INT);
    $ref->bindValue(':cid', $category_id, PDO::PARAM_INT);
    $ref->execute();
    $row = $ref->fetch(PDO::FETCH_ASSOC);
    respond(['recipe_category' => $row], 200);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
