<?php
/**
 * add_user.php
 *
 * Usage (HTTP):
 *   POST /src/add_user.php
 *   Content-Type: application/json
 *   Body: {"username":"alice","email":"a@x.com","password":"secretpass","role":"user"}
 *
 * Usage (CLI):
 *   php src/add_user.php --username=alice --email=a@x.com --password=secretpass [--role=user]
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app_helpers.php';

function validate_add_input(array $in): array
{
    $username = trim((string)($in['username'] ?? ''));
    $email = trim((string)($in['email'] ?? ''));
    $password = (string)($in['password'] ?? '');
    $role = trim((string)($in['role'] ?? 'user'));

    $errors = [];
    if ($username === '') $errors['username'] = 'Username is required';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required';
    if ($password === '' || strlen($password) < 8) $errors['password'] = 'Password required (min 8 chars)';
    if ($role === '') $role = 'user';

    return [$errors, ['username' => $username, 'email' => $email, 'password' => $password, 'role' => $role]];
}

// Main
try {
    $input = get_input();
    [$errors, $clean] = validate_add_input($input);
    if (!empty($errors)) {
        respond(['errors' => $errors], 422);
    }

    $pdo = get_pdo();

    $sql = "INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)";
    $stmt = $pdo->prepare($sql);
    $passwordHash = password_hash($clean['password'], PASSWORD_DEFAULT);

    $stmt->bindValue(':username', $clean['username'], PDO::PARAM_STR);
    $stmt->bindValue(':email', $clean['email'], PDO::PARAM_STR);
    $stmt->bindValue(':password_hash', $passwordHash, PDO::PARAM_STR);
    $stmt->bindValue(':role', $clean['role'], PDO::PARAM_STR);

    $stmt->execute();

    $id = (int)$pdo->lastInsertId();

    respond(['id' => $id, 'username' => $clean['username'], 'email' => $clean['email'], 'role' => $clean['role']], 201);

} catch (PDOException $e) {
    // handle duplicate unique constraint (MySQL: SQLSTATE 23000)
    if ($e->getCode() === '23000') {
        $field = detect_duplicate_field($e);
        respond(['error' => 'Duplicate entry', 'field' => $field, 'detail' => $e->getMessage()], 409);
    }
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
