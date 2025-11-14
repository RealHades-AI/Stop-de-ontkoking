<?php
/**
 * app_helpers.php
 *
 * Shared helper utilities for CLI/HTTP input handling and common user operations.
 */

function is_cli(): bool
{
    return php_sapi_name() === 'cli' || defined('STDIN');
}

function respond($payload, int $status = 200)
{
    if (is_cli()) {
        echo json_encode(['status' => $status, 'data' => $payload], JSON_PRETTY_PRINT) . PHP_EOL;
        exit($status >= 400 ? 1 : 0);
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function parse_cli_args(array $argv): array
{
    $data = [];
    foreach ($argv as $arg) {
        if (strpos($arg, '--') !== 0) continue;
        $pair = substr($arg, 2);
        $parts = explode('=', $pair, 2);
        $key = $parts[0];
        $val = $parts[1] ?? '';
        $data[$key] = $val;
    }
    return $data;
}

function get_input(): array
{
    if (is_cli()) {
        return parse_cli_args($_SERVER['argv'] ?? []);
    }

    // For HTTP requests, accept GET (query parameters) or POST (JSON body)
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        return $_GET;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            respond(['error' => 'Invalid JSON body'], 400);
        }
        return $json;
    }

    respond(['error' => 'Unsupported HTTP method'], 405);
}

function validate_identifier(array $in): array
{
    $id = isset($in['id']) ? (int)$in['id'] : null;
    $username = isset($in['username']) ? trim((string)$in['username']) : null;
    $email = isset($in['email']) ? trim((string)$in['email']) : null;
    if ($id === null && !$username && !$email) {
        return [false, 'Provide one of: id, username or email'];
    }
    return [true, ['id' => $id, 'username' => $username, 'email' => $email]];
}

function find_user_by_identifier(PDO $pdo, array $ident): ?array
{
    $where = [];
    $params = [];
    if (!empty($ident['id'])) {
        $where[] = 'id = :id';
        $params[':id'] = (int)$ident['id'];
    }
    if (!empty($ident['username'])) {
        $where[] = 'username = :username';
        $params[':username'] = $ident['username'];
    }
    if (!empty($ident['email'])) {
        $where[] = 'email = :email';
        $params[':email'] = $ident['email'];
    }

    if (empty($where)) return null;

    $sql = 'SELECT id, username, email, role, created_at, updated_at FROM users WHERE ' . implode(' OR ', $where) . ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function detect_duplicate_field(PDOException $e): ?string
{
    $msg = $e->getMessage();
    if (stripos($msg, 'username') !== false) return 'username';
    if (stripos($msg, 'email') !== false) return 'email';
    return null;
}

/**
 * Create a URL-friendly slug from text.
 */
function slugify(string $text): string
{
    $text = preg_replace('~[^\\pL\
d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-a-zA-Z0-9_]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) return 'cat';
    return $text;
}

/**
 * Ensure a category with the given name exists. If not, create it and return its id.
 * This handles slug uniqueness by appending suffixes when needed.
 */
function ensure_category_exists(PDO $pdo, string $name): int
{
    $name = trim($name);
    if ($name === '') throw new Exception('Category name cannot be empty');

    // Try to find by name first
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return (int)$row['id'];

    // Generate slug and attempt to insert. Ensure slug uniqueness by appending suffix if needed.
    $baseSlug = slugify($name);
    $slug = $baseSlug;
    $i = 1;
    while (true) {
        try {
            $ins = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)');
            $ins->bindValue(':name', $name, PDO::PARAM_STR);
            $ins->bindValue(':slug', $slug, PDO::PARAM_STR);
            $ins->execute();
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $stmt2 = $pdo->prepare('SELECT id FROM categories WHERE name = :name OR slug = :slug LIMIT 1');
                $stmt2->bindValue(':name', $name, PDO::PARAM_STR);
                $stmt2->bindValue(':slug', $slug, PDO::PARAM_STR);
                $stmt2->execute();
                $r = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($r) return (int)$r['id'];
                $slug = $baseSlug . '-' . $i;
                $i++;
                continue;
            }
            throw $e;
        }
    }
}

/**
 * Find a recipe by id and return basic fields or null.
 */
function find_recipe_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, user_id, category_id, title, description, servings, prep_time_minutes, cook_time_minutes, difficulty, is_published, created_at, updated_at FROM recipes WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
