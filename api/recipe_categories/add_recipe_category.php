<?php
/**
 * add_recipe_category.php
 *
 * Attach a category to a recipe.
 * POST JSON: { "recipe_id": 1, "category_id": 2, "is_primary": true, "sort_order": 0 }
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $recipe_id = isset($in['recipe_id']) ? (int)$in['recipe_id'] : null;
    $category_id = isset($in['category_id']) ? (int)$in['category_id'] : null;
    if (empty($recipe_id) || empty($category_id)) respond(['error' => 'recipe_id and category_id are required'], 400);

    $is_primary = !empty($in['is_primary']) ? 1 : 0;
    $sort_order = isset($in['sort_order']) ? (int)$in['sort_order'] : 0;

    $pdo = get_pdo();

    // Verify recipe exists
    $r = find_recipe_by_id($pdo, $recipe_id);
    if (!$r) respond(['error' => 'Recipe not found'], 404);

    // Verify category exists
    $cstmt = $pdo->prepare('SELECT id FROM categories WHERE id = :id LIMIT 1');
    $cstmt->bindValue(':id', $category_id, PDO::PARAM_INT);
    $cstmt->execute();
    if (!$cstmt->fetchColumn()) respond(['error' => 'Category not found'], 404);

    $pdo->beginTransaction();

    if ($is_primary) {
        // clear other primary flags for this recipe
        $clear = $pdo->prepare('UPDATE recipe_categories SET is_primary = 0 WHERE recipe_id = :rid');
        $clear->bindValue(':rid', $recipe_id, PDO::PARAM_INT);
        $clear->execute();
    }

    // Use INSERT ... ON DUPLICATE KEY UPDATE to upsert
    $ins = $pdo->prepare('INSERT INTO recipe_categories (recipe_id, category_id, is_primary, sort_order) VALUES (:rid, :cid, :is_primary, :sort_order) ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary), sort_order = VALUES(sort_order)');
    $ins->bindValue(':rid', $recipe_id, PDO::PARAM_INT);
    $ins->bindValue(':cid', $category_id, PDO::PARAM_INT);
    $ins->bindValue(':is_primary', $is_primary, PDO::PARAM_INT);
    $ins->bindValue(':sort_order', $sort_order, PDO::PARAM_INT);
    $ins->execute();

    $pdo->commit();

    $ref = $pdo->prepare('SELECT recipe_id, category_id, is_primary, sort_order FROM recipe_categories WHERE recipe_id = :rid AND category_id = :cid LIMIT 1');
    $ref->bindValue(':rid', $recipe_id, PDO::PARAM_INT);
    $ref->bindValue(':cid', $category_id, PDO::PARAM_INT);
    $ref->execute();
    $row = $ref->fetch(PDO::FETCH_ASSOC);
    respond(['recipe_category' => $row], 201);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
