<?php
/**
 * get_category.php
 *
 * Get a single category by id/name or list categories with pagination.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $pdo = get_pdo();

    // single fetch by id or name
    if (isset($in['id']) || isset($in['name']) || isset($in['slug'])) {
        $where = [];
        $params = [];
        if (isset($in['id'])) { $where[] = 'id = :id'; $params[':id'] = (int)$in['id']; }
        if (isset($in['name'])) { $where[] = 'name = :name'; $params[':name'] = $in['name']; }
        if (isset($in['slug'])) { $where[] = 'slug = :slug'; $params[':slug'] = $in['slug']; }
        $sql = 'SELECT id, name, slug FROM categories WHERE ' . implode(' OR ', $where) . ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) respond(['error' => 'Category not found'], 404);
        respond(['category' => $row], 200);
    }

    // list
    $perPage = isset($in['per_page']) ? max(1, (int)$in['per_page']) : 25;
    $page = isset($in['page']) ? max(1, (int)$in['page']) : 1;
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];
    if (!empty($in['q'])) {
        $where[] = '(name LIKE :q OR slug LIKE :q)';
        $params[':q'] = '%' . str_replace('%', '\\%', $in['q']) . '%';
    }

    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));
    $countSql = "SELECT COUNT(*) FROM categories {$whereSql}";
    $cstmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $cstmt->bindValue($k, $v);
    $cstmt->execute();
    $total = (int)$cstmt->fetchColumn();

    $sql = "SELECT id, name, slug FROM categories {$whereSql} ORDER BY name ASC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $meta = ['total' => $total, 'per_page' => $perPage, 'page' => $page, 'pages' => (int)ceil($total / max(1, $perPage))];
    respond(['categories' => $rows, 'meta' => $meta], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
