<?php
/**
 * edit_user.php
 *
 * Usage (HTTP):
 *   POST /src/edit_user.php
 *   Content-Type: application/json
 *   Body: {"id":123, "username":"newname", "email":"new@x.com", "password":"newpass", "role":"admin"}
 *
 * Usage (CLI):
 *   php src/edit_user.php --id=123 [--username=newname] [--email=new@example.com] [--password=newpass] [--role=admin]
 *
 * Behavior:
 *  - Must provide one identifier: id OR username OR email to find the user.
 *  - Only provided fields will be updated. Password is hashed when provided.
 *  - Returns JSON with updated user fields.
 */


require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

function validate_identify(array $in)
{
    $id = isset($in['id']) ? (int)$in['id'] : null;
    $username = isset($in['username']) ? trim((string)$in['username']) : null;
    $email = isset($in['email']) ? trim((string)$in['email']) : null;
    if ($id === null && !$username && !$email) {
        return [false, 'Provide one of: id, username or email to identify the user'];
    }
    return [true, ['id' => $id, 'username' => $username, 'email' => $email]];
}

function validate_updates(array $in)
{
    $allowed = ['username', 'email', 'password', 'role'];
    $updates = [];
    foreach ($allowed as $k) {
        if (array_key_exists($k, $in)) {
            $updates[$k] = $in[$k];
        }
    }
    if (empty($updates)) return [false, 'No updatable fields provided'];

    $errors = [];
    if (isset($updates['username'])) {
        $u = trim((string)$updates['username']);
        if ($u === '') $errors['username'] = 'Username cannot be empty';
        $updates['username'] = $u;
    }
    if (isset($updates['email'])) {
        $e = trim((string)$updates['email']);
        if ($e === '' || !filter_var($e, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email required';
        $updates['email'] = $e;
    }
    if (isset($updates['password'])) {
        $p = (string)$updates['password'];
        if ($p === '' || strlen($p) < 8) $errors['password'] = 'Password must be at least 8 characters';
        // we will hash later
        $updates['password'] = $p;
    }
    if (isset($updates['role'])) {
        $r = trim((string)$updates['role']);
        if ($r === '') $errors['role'] = 'Role cannot be empty';
        $updates['role'] = $r;
    }

    if (!empty($errors)) return [false, $errors];
    return [true, $updates];
}

try {

    $input = get_input();

    [$ok, $ident] = validate_identifier($input);
    if (!$ok) respond(['error' => $ident], 422);

    [$ok2, $updates] = validate_updates($input);
    if (!$ok2) respond(['error' => $updates], 422);

    $pdo = get_pdo();

    $user = find_user_by_identifier($pdo, $ident);
    if (!$user) respond(['error' => 'User not found'], 404);

    // Build update clause
    $set = [];
    $binds = [];
    if (isset($updates['username'])) {
        $set[] = 'username = :username';
        $binds[':username'] = $updates['username'];
    }
    if (isset($updates['email'])) {
        $set[] = 'email = :email';
        $binds[':email'] = $updates['email'];
    }
    if (isset($updates['password'])) {
        $set[] = 'password_hash = :password_hash';
        $binds[':password_hash'] = password_hash($updates['password'], PASSWORD_DEFAULT);
    }
    if (isset($updates['role'])) {
        $set[] = 'role = :role';
        $binds[':role'] = $updates['role'];
    }

    if (empty($set)) respond(['error' => 'No valid fields to update'], 400);

    $updateSql = 'UPDATE users SET ' . implode(', ', $set) . ' WHERE id = :id';
    $upd = $pdo->prepare($updateSql);
    foreach ($binds as $k => $v) $upd->bindValue($k, $v);
    $upd->bindValue(':id', (int)$user['id'], PDO::PARAM_INT);
    $upd->execute();

    // Return the updated row
    $ref = $pdo->prepare('SELECT id, username, email, role, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
    $ref->bindValue(':id', (int)$user['id'], PDO::PARAM_INT);
    $ref->execute();
    $updated = $ref->fetch(PDO::FETCH_ASSOC);

    respond(['updated' => $updated], 200);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $msg = $e->getMessage();
        $field = null;
        if (stripos($msg, 'username') !== false) $field = 'username';
        if (stripos($msg, 'email') !== false) $field = 'email';
        respond(['error' => 'Duplicate entry', 'field' => $field, 'detail' => $msg], 409);
    }
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
