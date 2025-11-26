<?php
/**
 * delete_recipe_category.php
 *
 * Remove a category association from a recipe. Requires recipe_id and category_id.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $recipe_id = isset($in['recipe_id']) ? (int)$in['recipe_id'] : null;
    $category_id = isset($in['category_id']) ? (int)$in['category_id'] : null;
    if (empty($recipe_id) || empty($category_id)) respond(['error' => 'recipe_id and category_id are required'], 400);

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT 1 FROM recipe_categories WHERE recipe_id = :rid AND category_id = :cid LIMIT 1');
    $stmt->bindValue(':rid', $recipe_id, PDO::PARAM_INT);
    $stmt->bindValue(':cid', $category_id, PDO::PARAM_INT);
    $stmt->execute();
    if (!$stmt->fetchColumn()) respond(['error' => 'Association not found'], 404);

    $del = $pdo->prepare('DELETE FROM recipe_categories WHERE recipe_id = :rid AND category_id = :cid');
    $del->bindValue(':rid', $recipe_id, PDO::PARAM_INT);
    $del->bindValue(':cid', $category_id, PDO::PARAM_INT);
    $del->execute();

    respond(['deleted' => true, 'recipe_id' => $recipe_id, 'category_id' => $category_id], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
