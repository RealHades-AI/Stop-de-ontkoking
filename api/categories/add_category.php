<?php
/**
 * add_category.php
 *
 * Create a new category.
 *
 * HTTP POST JSON: { "name": "Breakfast", "slug": "breakfast" }
 * CLI: php src/categories/add_category.php --name="Breakfast" [--slug=breakfast]
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

try {
    $in = get_input();

    $name = isset($in['name']) ? trim((string)$in['name']) : '';
    $slug = isset($in['slug']) ? trim((string)$in['slug']) : null;
    if ($name === '') respond(['error' => 'name is required'], 422);

    $pdo = get_pdo();

    // Try to insert; handle duplicates gracefully
    $finalSlug = $slug !== null ? slugify($slug) : slugify($name);
    $i = 1;
    while (true) {
        try {
            $ins = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)');
            $ins->bindValue(':name', $name, PDO::PARAM_STR);
            $ins->bindValue(':slug', $finalSlug, PDO::PARAM_STR);
            $ins->execute();
            $id = (int)$pdo->lastInsertId();
            respond(['id' => $id, 'name' => $name, 'slug' => $finalSlug], 201);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                // find existing by name or slug
                $stmt = $pdo->prepare('SELECT id, name, slug FROM categories WHERE name = :name OR slug = :slug LIMIT 1');
                $stmt->bindValue(':name', $name, PDO::PARAM_STR);
                $stmt->bindValue(':slug', $finalSlug, PDO::PARAM_STR);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    respond(['error' => 'Category already exists', 'existing' => $row], 409);
                }
                // otherwise try a different slug
                $finalSlug = slugify($name) . '-' . $i;
                $i++;
                continue;
            }
            throw $e;
        }
    }

} catch (PDOException $e) {
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
