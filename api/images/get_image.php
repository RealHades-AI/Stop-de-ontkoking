<?php
/**
 * get_image.php
 *
 * Get image by id or list images by owner_type/owner_id.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();
    $pdo = get_pdo();

    if (isset($in['id'])) {
        $id = (int)$in['id'];
        $stmt = $pdo->prepare('SELECT * FROM images WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) respond(['error' => 'Image not found'], 404);
        respond(['image' => $row], 200);
    }

    $perPage = isset($in['per_page']) ? max(1, (int)$in['per_page']) : 25;
    $page = isset($in['page']) ? max(1, (int)$in['page']) : 1;
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];
    if (!empty($in['owner_type'])) { $where[] = 'owner_type = :owner_type'; $params[':owner_type'] = $in['owner_type']; }
    if (!empty($in['owner_id'])) { $where[] = 'owner_id = :owner_id'; $params[':owner_id'] = (int)$in['owner_id']; }

    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));
    $countSql = "SELECT COUNT(*) FROM images {$whereSql}";
    $cstmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $cstmt->bindValue($k, $v);
    $cstmt->execute();
    $total = (int)$cstmt->fetchColumn();

    $sql = "SELECT * FROM images {$whereSql} ORDER BY is_primary DESC, id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $meta = ['total' => $total, 'per_page' => $perPage, 'page' => $page, 'pages' => (int)ceil($total / max(1, $perPage))];
    respond(['images' => $rows, 'meta' => $meta], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
