<?php
/**
 * get_recipe_categories.php
 *
 * Get associations. Query by recipe_id or category_id, or list all with pagination.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $pdo = get_pdo();

    if (!empty($in['recipe_id']) && !empty($in['category_id'])) {
        $stmt = $pdo->prepare('SELECT recipe_id, category_id, is_primary, sort_order FROM recipe_categories WHERE recipe_id = :rid AND category_id = :cid LIMIT 1');
        $stmt->bindValue(':rid', (int)$in['recipe_id'], PDO::PARAM_INT);
        $stmt->bindValue(':cid', (int)$in['category_id'], PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) respond(['error' => 'Association not found'], 404);
        respond(['recipe_category' => $row], 200);
    }

    $perPage = isset($in['per_page']) ? max(1, (int)$in['per_page']) : 25;
    $page = isset($in['page']) ? max(1, (int)$in['page']) : 1;
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];
    if (!empty($in['recipe_id'])) { $where[] = 'recipe_id = :recipe_id'; $params[':recipe_id'] = (int)$in['recipe_id']; }
    if (!empty($in['category_id'])) { $where[] = 'category_id = :category_id'; $params[':category_id'] = (int)$in['category_id']; }

    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

    $countSql = "SELECT COUNT(*) FROM recipe_categories {$whereSql}";
    $cstmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $cstmt->bindValue($k, $v, PDO::PARAM_INT);
    $cstmt->execute();
    $total = (int)$cstmt->fetchColumn();

    $sql = "SELECT recipe_id, category_id, is_primary, sort_order FROM recipe_categories {$whereSql} ORDER BY is_primary DESC, sort_order ASC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $meta = ['total' => $total, 'per_page' => $perPage, 'page' => $page, 'pages' => (int)ceil($total / max(1, $perPage))];
    respond(['recipe_categories' => $rows, 'meta' => $meta], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
