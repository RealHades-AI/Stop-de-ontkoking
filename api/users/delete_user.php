<?php
/**
 * delete_user.php
 *
 * Usage (HTTP):
 *   POST /src/delete_user.php
 *   Content-Type: application/json
 *   Body: {"id":123} OR {"username":"alice"} OR {"email":"a@x.com"}
 *
 * Usage (CLI):
 *   php src/delete_user.php --id=123
 *   php src/delete_user.php --username=alice
 *   php src/delete_user.php --email=alice@example.com
 *
 * Note: Deleting a user will cascade to related rows according to DB foreign keys
 * (e.g. recipes have ON DELETE CASCADE in the provided migrations). Ensure this is desired.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app_helpers.php';

try {
    $input = get_input();
    [$ok, $payload] = validate_identifier($input);
    if (!$ok) {
        respond(['error' => $payload], 422);
    }

    $pdo = get_pdo();

    $user = find_user_by_identifier($pdo, $payload);
    if (!$user) {
        respond(['error' => 'User not found'], 404);
    }

    // Delete by id (most precise)
    $del = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $del->bindValue(':id', (int)$user['id'], PDO::PARAM_INT);
    $del->execute();

    respond(['deleted' => ['id' => (int)$user['id'], 'username' => $user['username'], 'email' => $user['email'], 'role' => $user['role']]], 200);

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
