<?php
/**
 * get_review.php
 *
 * Get a single review by id or list reviews by recipe_id or user_id with pagination.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $pdo = get_pdo();

    if (isset($in['id'])) {
        $id = (int)$in['id'];
        $stmt = $pdo->prepare('SELECT id, recipe_id, user_id, rating, title, text, created_at, updated_at FROM recipe_reviews WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) respond(['error' => 'Review not found'], 404);
        respond(['review' => $row], 200);
    }

    $perPage = isset($in['per_page']) ? max(1, (int)$in['per_page']) : 25;
    $page = isset($in['page']) ? max(1, (int)$in['page']) : 1;
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];
    if (!empty($in['recipe_id'])) { $where[] = 'recipe_id = :recipe_id'; $params[':recipe_id'] = (int)$in['recipe_id']; }
    if (!empty($in['user_id'])) { $where[] = 'user_id = :user_id'; $params[':user_id'] = (int)$in['user_id']; }

    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));
    $countSql = "SELECT COUNT(*) FROM recipe_reviews {$whereSql}";
    $cstmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $cstmt->bindValue($k, $v);
    $cstmt->execute();
    $total = (int)$cstmt->fetchColumn();

    $sql = "SELECT id, recipe_id, user_id, rating, title, text, created_at, updated_at FROM recipe_reviews {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $meta = ['total' => $total, 'per_page' => $perPage, 'page' => $page, 'pages' => (int)ceil($total / max(1, $perPage))];
    respond(['reviews' => $rows, 'meta' => $meta], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
