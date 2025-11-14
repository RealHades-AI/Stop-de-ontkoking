<?php
/**
 * get_user.php
 *
 * Fetch a single user or list users.
 *
 * HTTP GET example (single):
 *   GET /src/get_user.php?id=123
 *   GET /src/get_user.php?username=alice
 *
 * HTTP GET example (list):
 *   GET /src/get_user.php?per_page=20&page=1
 *   GET /src/get_user.php?role=admin&per_page=10&page=2
 *
 * CLI examples:
 *   php src/get_user.php --id=123
 *   php src/get_user.php --username=alice
 *   php src/get_user.php --list --per_page=50 --page=1
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app_helpers.php';

try {
    $input = get_input();

    // Determine if this is a single fetch or a list
    $isList = false;
    if (is_cli()) {
        $isList = isset($input['list']) || (!isset($input['id']) && !isset($input['username']) && !isset($input['email']));
    } else {
        $isList = isset($input['list']) || (!isset($input['id']) && !isset($input['username']) && !isset($input['email']));
    }

    $pdo = get_pdo();

    if (!$isList) {
        [$ok, $ident] = validate_identifier($input);
        if (!$ok) respond(['error' => $ident], 422);

        $user = find_user_by_identifier($pdo, $ident);
        if (!$user) respond(['error' => 'User not found'], 404);
        respond(['user' => $user], 200);
    }

    // Listing: pagination + optional filters
    $perPage = isset($input['per_page']) ? max(1, (int)$input['per_page']) : 25;
    $page = isset($input['page']) ? max(1, (int)$input['page']) : 1;
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];
    if (!empty($input['role'])) {
        $where[] = 'role = :role';
        $params[':role'] = $input['role'];
    }

    // Simple search by username/email substring
    if (!empty($input['q'])) {
        $where[] = '(username LIKE :q OR email LIKE :q)';
        $params[':q'] = '%' . str_replace('%', '\\%', $input['q']) . '%';
    }

    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

    $countSql = "SELECT COUNT(*) AS cnt FROM users {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT id, username, email, role, created_at, updated_at FROM users {$whereSql} ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $meta = [
        'total' => $total,
        'per_page' => $perPage,
        'page' => $page,
        'pages' => (int)ceil($total / max(1, $perPage)),
    ];

    respond(['users' => $users, 'meta' => $meta], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
