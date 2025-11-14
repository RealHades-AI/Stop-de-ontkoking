<?php
/**
 * add_review.php
 *
 * Create a review for a recipe.
 * HTTP POST JSON: { "recipe_id":123, "user_id":1, "rating":5, "title":"Great", "text":"Nice" }
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $recipeId = isset($in['recipe_id']) ? (int)$in['recipe_id'] : null;
    $userId = isset($in['user_id']) ? (int)$in['user_id'] : null;
    $rating = isset($in['rating']) ? (int)$in['rating'] : null;
    $title = isset($in['title']) ? trim((string)$in['title']) : null;
    $text = isset($in['text']) ? (string)$in['text'] : null;

    $errs = [];
    if (!$recipeId) $errs['recipe_id'] = 'recipe_id is required';
    if ($rating === null || $rating < 1 || $rating > 5) $errs['rating'] = 'rating is required and must be between 1 and 5';
    if (!empty($errs)) respond(['errors' => $errs], 422);

    $pdo = get_pdo();

    // ensure recipe exists
    $recipe = find_recipe_by_id($pdo, $recipeId);
    if (!$recipe) respond(['error' => 'Recipe not found', 'field' => 'recipe_id'], 422);

    // if user_id provided ensure exists
    if ($userId) {
        $u = find_user_by_identifier($pdo, ['id' => $userId]);
        if (!$u) respond(['error' => 'User not found', 'field' => 'user_id'], 422);
    }

    $stmt = $pdo->prepare('INSERT INTO recipe_reviews (recipe_id, user_id, rating, title, text) VALUES (:recipe_id, :user_id, :rating, :title, :text)');
    $stmt->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId !== null ? $userId : null, PDO::PARAM_INT);
    $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':text', $text, PDO::PARAM_STR);
    $stmt->execute();
    $id = (int)$pdo->lastInsertId();

    $ref = $pdo->prepare('SELECT id, recipe_id, user_id, rating, title, text, created_at, updated_at FROM recipe_reviews WHERE id = :id LIMIT 1');
    $ref->bindValue(':id', $id, PDO::PARAM_INT);
    $ref->execute();
    $row = $ref->fetch(PDO::FETCH_ASSOC);
    respond(['review' => $row], 201);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
