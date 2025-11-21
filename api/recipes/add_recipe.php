<?php
/**
 * add_recipe.php
 *
 * Create a recipe (optionally with ingredients and steps).
 *
 * HTTP usage:
 *   POST /src/recipes/add_recipe.php
 *   Content-Type: application/json
 *   Body example:
 *   {
 *     "user_id": 1,
 *     "category_id": 2,
 *     "title": "Pancakes",
 *     "description": "Fluffy pancakes",
 *     "servings": 4,
 *     "prep_time_minutes": 10,
 *     "cook_time_minutes": 15,
 *     "difficulty": "easy",
 *     "is_published": 1,
 *     "ingredients": [ {"quantity_text":"1 cup","ingredient_text":"flour"}, {"quantity_text":"1","ingredient_text":"egg"} ],
 *     "steps": [ {"instruction_text":"Mix ingredients"}, {"instruction_text":"Cook on pan"} ]
 *   }
 *
 * CLI usage (provide JSON string via --data):
 *   php src/recipes/add_recipe.php --data='{...}'
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

function validate_input(array $in): array
{
    $errors = [];
    $user_id = isset($in['user_id']) ? (int)$in['user_id'] : null;
    $title = isset($in['title']) ? trim((string)$in['title']) : '';

    if (!$user_id || $user_id <= 0) $errors['user_id'] = 'user_id is required and must be a positive integer';
    if ($title === '') $errors['title'] = 'title is required';

    // optional arrays
    $ingredients = [];
    if (isset($in['ingredients'])) {
        if (!is_array($in['ingredients'])) {
            $errors['ingredients'] = 'ingredients must be an array';
        } else {
            foreach ($in['ingredients'] as $i => $it) {
                if (!is_array($it) || empty($it['ingredient_text'])) {
                    $errors["ingredients.$i"] = 'ingredient_text is required';
                } else {
                    $ingredients[] = [
                        'quantity_text' => isset($it['quantity_text']) ? (string)$it['quantity_text'] : null,
                        'ingredient_text' => (string)$it['ingredient_text'],
                        'sort_order' => isset($it['sort_order']) ? (int)$it['sort_order'] : $i,
                    ];
                }
            }
        }
    }

    $steps = [];
    if (isset($in['steps'])) {
        if (!is_array($in['steps'])) {
            $errors['steps'] = 'steps must be an array';
        } else {
            foreach ($in['steps'] as $i => $st) {
                if (!is_array($st) || empty($st['instruction_text'])) {
                    $errors["steps.$i"] = 'instruction_text is required';
                } else {
                    $steps[] = [
                        'instruction_text' => (string)$st['instruction_text'],
                        'sort_order' => isset($st['sort_order']) ? (int)$st['sort_order'] : $i,
                    ];
                }
            }
        }
    }

    if (!empty($errors)) return [false, $errors];

    // Accept either category_id (int) or category (string name)
    $payload = [
        'user_id' => $user_id,
        'category_id' => isset($in['category_id']) ? (int)$in['category_id'] : null,
        'category_name' => isset($in['category']) ? (string)$in['category'] : null,
        'title' => $title,
        'description' => isset($in['description']) ? (string)$in['description'] : null,
        'servings' => isset($in['servings']) ? (int)$in['servings'] : null,
        'prep_time_minutes' => isset($in['prep_time_minutes']) ? (int)$in['prep_time_minutes'] : null,
        'cook_time_minutes' => isset($in['cook_time_minutes']) ? (int)$in['cook_time_minutes'] : null,
        'difficulty' => isset($in['difficulty']) ? (string)$in['difficulty'] : null,
        'is_published' => isset($in['is_published']) ? (int)$in['is_published'] : 0,
        'ingredients' => $ingredients,
        'steps' => $steps,
    ];

    return [true, $payload];
}

// category helpers moved to app_helpers.php (slugify, ensure_category_exists)

try {
    $in = get_input();

    // If CLI and a --data JSON is provided, decode it
    if (is_cli() && isset($in['data'])) {
        $decoded = json_decode($in['data'], true);
        if (is_array($decoded)) $in = $decoded;
    }

    [$ok, $val] = validate_input($in);
    if (!$ok) respond(['errors' => $val], 422);

    $pdo = get_pdo();
    $pdo->beginTransaction();

    // Verify user exists using shared helper
    $userRow = find_user_by_identifier($pdo, ['id' => $val['user_id']]);
    if (!$userRow) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        respond(['error' => 'User not found', 'field' => 'user_id'], 422);
    }

    // Resolve category: if category_name provided, find or create it; if category_id provided, ensure it exists
    $resolvedCategoryId = null;
    if (!empty($val['category_name'])) {
        $resolvedCategoryId = ensure_category_exists($pdo, $val['category_name']);
    } elseif (!empty($val['category_id'])) {
        $cStmt = $pdo->prepare('SELECT id FROM categories WHERE id = :id LIMIT 1');
        $cStmt->bindValue(':id', $val['category_id'], PDO::PARAM_INT);
        $cStmt->execute();
        $cRow = $cStmt->fetch(PDO::FETCH_ASSOC);
        if (!$cRow) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            respond(['error' => 'Category id not found', 'field' => 'category_id'], 422);
        }
        $resolvedCategoryId = (int)$val['category_id'];
    }

    $sql = "INSERT INTO recipes (user_id, category_id, title, description, servings, prep_time_minutes, cook_time_minutes, difficulty, is_published) VALUES (:user_id, :category_id, :title, :description, :servings, :prep_time_minutes, :cook_time_minutes, :difficulty, :is_published)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $val['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':category_id', $resolvedCategoryId !== null ? $resolvedCategoryId : null, PDO::PARAM_INT);
    $stmt->bindValue(':title', $val['title'], PDO::PARAM_STR);
    $stmt->bindValue(':description', $val['description'], PDO::PARAM_STR);
    $stmt->bindValue(':servings', $val['servings'] !== null ? $val['servings'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':prep_time_minutes', $val['prep_time_minutes'] !== null ? $val['prep_time_minutes'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':cook_time_minutes', $val['cook_time_minutes'] !== null ? $val['cook_time_minutes'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':difficulty', $val['difficulty'], PDO::PARAM_STR);
    $stmt->bindValue(':is_published', $val['is_published'], PDO::PARAM_INT);
    $stmt->execute();

    $recipeId = (int)$pdo->lastInsertId();

    // Insert ingredients
    if (!empty($val['ingredients'])) {
        $insIng = $pdo->prepare('INSERT INTO ingredients (recipe_id, quantity_text, ingredient_text, sort_order) VALUES (:recipe_id, :quantity_text, :ingredient_text, :sort_order)');
        foreach ($val['ingredients'] as $ing) {
            $insIng->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
            $insIng->bindValue(':quantity_text', $ing['quantity_text'], PDO::PARAM_STR);
            $insIng->bindValue(':ingredient_text', $ing['ingredient_text'], PDO::PARAM_STR);
            $insIng->bindValue(':sort_order', $ing['sort_order'], PDO::PARAM_INT);
            $insIng->execute();
        }
    }

    // Insert steps
    if (!empty($val['steps'])) {
        $insStep = $pdo->prepare('INSERT INTO steps (recipe_id, instruction_text, sort_order) VALUES (:recipe_id, :instruction_text, :sort_order)');
        foreach ($val['steps'] as $st) {
            $insStep->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
            $insStep->bindValue(':instruction_text', $st['instruction_text'], PDO::PARAM_STR);
            $insStep->bindValue(':sort_order', $st['sort_order'], PDO::PARAM_INT);
            $insStep->execute();
        }
    }

    $pdo->commit();

    // Return created recipe with ingredients and steps
    $outStmt = $pdo->prepare('SELECT id, user_id, category_id, title, description, servings, prep_time_minutes, cook_time_minutes, difficulty, is_published, created_at, updated_at FROM recipes WHERE id = :id LIMIT 1');
    $outStmt->bindValue(':id', $recipeId, PDO::PARAM_INT);
    $outStmt->execute();
    $recipe = $outStmt->fetch(PDO::FETCH_ASSOC);

    $ingStmt = $pdo->prepare('SELECT id, quantity_text, ingredient_text, sort_order FROM ingredients WHERE recipe_id = :rid ORDER BY sort_order');
    $ingStmt->bindValue(':rid', $recipeId, PDO::PARAM_INT);
    $ingStmt->execute();
    $ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);

    $stepStmt = $pdo->prepare('SELECT id, instruction_text, sort_order FROM steps WHERE recipe_id = :rid ORDER BY sort_order');
    $stepStmt->bindValue(':rid', $recipeId, PDO::PARAM_INT);
    $stepStmt->execute();
    $steps = $stepStmt->fetchAll(PDO::FETCH_ASSOC);

    respond(['recipe' => $recipe, 'ingredients' => $ingredients, 'steps' => $steps], 201);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
