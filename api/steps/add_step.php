<?php
/**
 * add_step.php
 *
 * Add a single step for a recipe.
 * POST JSON: { "recipe_id": 1, "instruction_text": "Mix ingredients", "sort_order": 0 }
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $recipe_id = isset($in['recipe_id']) ? (int)$in['recipe_id'] : null;
    $instruction_text = isset($in['instruction_text']) ? trim($in['instruction_text']) : '';
    if (empty($recipe_id) || $instruction_text === '') respond(['error' => 'recipe_id and instruction_text are required'], 400);

    $sort_order = isset($in['sort_order']) ? (int)$in['sort_order'] : 0;

    $pdo = get_pdo();
    $r = find_recipe_by_id($pdo, $recipe_id);
    if (!$r) respond(['error' => 'Recipe not found'], 404);

    $stmt = $pdo->prepare('INSERT INTO steps (recipe_id, instruction_text, sort_order) VALUES (:recipe_id, :instruction_text, :sort_order)');
    $stmt->bindValue(':recipe_id', $recipe_id, PDO::PARAM_INT);
    $stmt->bindValue(':instruction_text', $instruction_text);
    $stmt->bindValue(':sort_order', $sort_order, PDO::PARAM_INT);
    $stmt->execute();

    $id = (int)$pdo->lastInsertId();
    $ref = $pdo->prepare('SELECT id, recipe_id, instruction_text, sort_order FROM steps WHERE id = :id LIMIT 1');
    $ref->bindValue(':id', $id, PDO::PARAM_INT);
    $ref->execute();
    $row = $ref->fetch(PDO::FETCH_ASSOC);
    respond(['step' => $row], 201);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
