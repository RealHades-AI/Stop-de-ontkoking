<?php
/**
 * add_ingredient.php
 *
 * Add a single ingredient for a recipe.
 * POST JSON: { "recipe_id": 1, "quantity_text": "1 cup", "ingredient_text": "flour", "sort_order": 0 }
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $recipe_id = isset($in['recipe_id']) ? (int)$in['recipe_id'] : null;
    $ingredient_text = isset($in['ingredient_text']) ? trim($in['ingredient_text']) : '';
    if (empty($recipe_id) || $ingredient_text === '') respond(['error' => 'recipe_id and ingredient_text are required'], 400);

    $quantity_text = isset($in['quantity_text']) ? trim($in['quantity_text']) : null;
    $sort_order = isset($in['sort_order']) ? (int)$in['sort_order'] : 0;

    $pdo = get_pdo();
    // verify recipe exists
    $r = find_recipe_by_id($pdo, $recipe_id);
    if (!$r) respond(['error' => 'Recipe not found'], 404);

    $stmt = $pdo->prepare('INSERT INTO ingredients (recipe_id, quantity_text, ingredient_text, sort_order) VALUES (:recipe_id, :quantity_text, :ingredient_text, :sort_order)');
    $stmt->bindValue(':recipe_id', $recipe_id, PDO::PARAM_INT);
    $stmt->bindValue(':quantity_text', $quantity_text);
    $stmt->bindValue(':ingredient_text', $ingredient_text);
    $stmt->bindValue(':sort_order', $sort_order, PDO::PARAM_INT);
    $stmt->execute();

    $id = (int)$pdo->lastInsertId();
    $ref = $pdo->prepare('SELECT id, recipe_id, quantity_text, ingredient_text, sort_order FROM ingredients WHERE id = :id LIMIT 1');
    $ref->bindValue(':id', $id, PDO::PARAM_INT);
    $ref->execute();
    $row = $ref->fetch(PDO::FETCH_ASSOC);
    respond(['ingredient' => $row], 201);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
