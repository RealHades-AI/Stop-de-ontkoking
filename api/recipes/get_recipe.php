<?php
/**
 * get_recipe.php
 *
 * Fetch a single recipe by id or list recipes with pagination and filters.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $pdo = get_pdo();

    // Single fetch
    if (isset($in['id'])) {
        $id = (int)$in['id'];
        $recipe = find_recipe_by_id($pdo, $id);
        if (!$recipe) respond(['error' => 'Recipe not found'], 404);

        $ingStmt = $pdo->prepare('SELECT id, quantity_text, ingredient_text, sort_order FROM ingredients WHERE recipe_id = :rid ORDER BY sort_order');
        $ingStmt->bindValue(':rid', $id, PDO::PARAM_INT);
        $ingStmt->execute();
        $ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);

        $stepStmt = $pdo->prepare('SELECT id, instruction_text, sort_order FROM steps WHERE recipe_id = :rid ORDER BY sort_order');
        $stepStmt->bindValue(':rid', $id, PDO::PARAM_INT);
        $stepStmt->execute();
        $steps = $stepStmt->fetchAll(PDO::FETCH_ASSOC);

        respond(['recipe' => $recipe, 'ingredients' => $ingredients, 'steps' => $steps], 200);
    }

    // List with pagination and filters
    $perPage = isset($in['per_page']) ? max(1, (int)$in['per_page']) : 25;
    $page = isset($in['page']) ? max(1, (int)$in['page']) : 1;
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];
    if (!empty($in['user_id'])) { $where[] = 'user_id = :user_id'; $params[':user_id'] = (int)$in['user_id']; }
    if (!empty($in['category_id'])) { $where[] = 'category_id = :category_id'; $params[':category_id'] = (int)$in['category_id']; }
    if (!empty($in['q'])) {
        $where[] = '(title LIKE :q OR description LIKE :q)';
        $params[':q'] = '%' . str_replace('%', '\\%', $in['q']) . '%';
    }

    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));
    $countSql = "SELECT COUNT(*) FROM recipes {$whereSql}";
    $cstmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $cstmt->bindValue($k, $v);
    $cstmt->execute();
    $total = (int)$cstmt->fetchColumn();

    $sql = "SELECT id, user_id, category_id, title, description, servings, prep_time_minutes, cook_time_minutes, difficulty, is_published, created_at, updated_at FROM recipes {$whereSql} ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $meta = ['total' => $total, 'per_page' => $perPage, 'page' => $page, 'pages' => (int)ceil($total / max(1, $perPage))];
    respond(['recipes' => $rows, 'meta' => $meta], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
