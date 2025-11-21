<?php
/**
 * delete_recipe.php
 *
 * Delete a recipe by id.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $pdo = get_pdo();

    $id = isset($in['id']) ? (int)$in['id'] : null;
    if (!$id) respond(['error' => 'id is required'], 422);

    $recipe = find_recipe_by_id($pdo, $id);
    if (!$recipe) respond(['error' => 'Recipe not found'], 404);

    // Delete
    $del = $pdo->prepare('DELETE FROM recipes WHERE id = :id');
    $del->bindValue(':id', $id, PDO::PARAM_INT);
    $del->execute();

    respond(['deleted' => $recipe], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
