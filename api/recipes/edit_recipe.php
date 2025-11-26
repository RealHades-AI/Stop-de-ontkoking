<?php
/**
 * edit_recipe.php
 *
 * Update recipe fields and optionally replace ingredients and steps.
 * HTTP POST JSON: { "id":123, "title":"New title", "category": "Breakfast", "ingredients": [...], "steps": [...] }
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_helpers.php';

function validate_updates(array $in): array
{
    $allowed = ['title', 'description', 'servings', 'prep_time_minutes', 'cook_time_minutes', 'difficulty', 'is_published', 'category_id', 'category', 'category_ids'];
    $updates = [];
    foreach ($allowed as $k) if (array_key_exists($k, $in)) $updates[$k] = $in[$k];
    // ingredients and steps handled separately
    $ingredients = $in['ingredients'] ?? null;
    $steps = $in['steps'] ?? null;
    if (empty($updates) && $ingredients === null && $steps === null) return [false, 'No updates provided'];
    // validate category_ids if present
    if (array_key_exists('category_ids', $in) && $in['category_ids'] !== null) {
        if (!is_array($in['category_ids'])) return [false, 'category_ids must be an array of integers'];
        // normalize to ints
        $ids = array_map('intval', $in['category_ids']);
        $updates['category_ids'] = $ids;
    }
    return [true, ['updates' => $updates, 'ingredients' => $ingredients, 'steps' => $steps]];
}

try {
    $in = get_input();
    $pdo = get_pdo();

    $id = isset($in['id']) ? (int)$in['id'] : null;
    if (!$id) respond(['error' => 'id is required'], 422);

    $recipe = find_recipe_by_id($pdo, $id);
    if (!$recipe) respond(['error' => 'Recipe not found'], 404);

    [$ok, $payload] = validate_updates($in);
    if (!$ok) respond(['error' => $payload], 422);

    $updates = $payload['updates'];
    $ingredients = $payload['ingredients'];
    $steps = $payload['steps'];

    $pdo->beginTransaction();

    // Resolve category
    $resolvedCategoryId = null;
    if (isset($updates['category'])) {
        $resolvedCategoryId = ensure_category_exists($pdo, (string)$updates['category']);
        $updates['category_id'] = $resolvedCategoryId;
        unset($updates['category']);
    } elseif (isset($updates['category_id'])) {
        $cStmt = $pdo->prepare('SELECT id FROM categories WHERE id = :id LIMIT 1');
        $cStmt->bindValue(':id', (int)$updates['category_id'], PDO::PARAM_INT);
        $cStmt->execute();
        if (!$cStmt->fetch(PDO::FETCH_ASSOC)) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            respond(['error' => 'Category id not found', 'field' => 'category_id'], 422);
        }
    }

    // Build update clause
    $set = [];
    $binds = [];
    foreach ($updates as $k => $v) {
        $set[] = "$k = :$k";
        $binds[":$k"] = $v;
    }
    if (!empty($set)) {
        $sql = 'UPDATE recipes SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        foreach ($binds as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Replace ingredients if provided
    if (is_array($ingredients)) {
        $del = $pdo->prepare('DELETE FROM ingredients WHERE recipe_id = :rid');
        $del->bindValue(':rid', $id, PDO::PARAM_INT);
        $del->execute();
        $insIng = $pdo->prepare('INSERT INTO ingredients (recipe_id, quantity_text, ingredient_text, sort_order) VALUES (:recipe_id, :quantity_text, :ingredient_text, :sort_order)');
        foreach ($ingredients as $i => $ing) {
            $insIng->bindValue(':recipe_id', $id, PDO::PARAM_INT);
            $insIng->bindValue(':quantity_text', $ing['quantity_text'] ?? null, PDO::PARAM_STR);
            $insIng->bindValue(':ingredient_text', $ing['ingredient_text'] ?? '', PDO::PARAM_STR);
            $insIng->bindValue(':sort_order', $ing['sort_order'] ?? $i, PDO::PARAM_INT);
            $insIng->execute();
        }
    }

    // Replace steps if provided
    if (is_array($steps)) {
        $del = $pdo->prepare('DELETE FROM steps WHERE recipe_id = :rid');
        $del->bindValue(':rid', $id, PDO::PARAM_INT);
        $del->execute();
        $insStep = $pdo->prepare('INSERT INTO steps (recipe_id, instruction_text, sort_order) VALUES (:recipe_id, :instruction_text, :sort_order)');
        foreach ($steps as $i => $st) {
            $insStep->bindValue(':recipe_id', $id, PDO::PARAM_INT);
            $insStep->bindValue(':instruction_text', $st['instruction_text'] ?? '', PDO::PARAM_STR);
            $insStep->bindValue(':sort_order', $st['sort_order'] ?? $i, PDO::PARAM_INT);
            $insStep->execute();
        }
    }

    $pdo->commit();

    // Sync recipe_categories if category_ids provided
    if (array_key_exists('category_ids', $updates)) {
        $categoryIds = $updates['category_ids']; // array of ints (may be empty to clear)

        // validate categories exist
        if (!empty($categoryIds)) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $cstmt = $pdo->prepare("SELECT id FROM categories WHERE id IN ($placeholders)");
            foreach ($categoryIds as $i => $cid) $cstmt->bindValue($i+1, (int)$cid, PDO::PARAM_INT);
            $cstmt->execute();
            $found = $cstmt->fetchAll(PDO::FETCH_COLUMN, 0);
            $missing = array_diff($categoryIds, array_map('intval', $found));
            if (!empty($missing)) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                respond(['error' => 'One or more category_ids not found', 'missing' => array_values($missing)], 422);
            }
        }

        // existing associations
        $exStmt = $pdo->prepare('SELECT category_id, is_primary, sort_order FROM recipe_categories WHERE recipe_id = :rid');
        $exStmt->bindValue(':rid', $id, PDO::PARAM_INT);
        $exStmt->execute();
        $existing = $exStmt->fetchAll(PDO::FETCH_ASSOC);
        $existingIds = array_map(fn($r) => (int)$r['category_id'], $existing);

        $toAdd = array_values(array_diff($categoryIds, $existingIds));
        $toRemove = array_values(array_diff($existingIds, $categoryIds));
        $toKeep = array_values(array_intersect($existingIds, $categoryIds));

        // Remove associations not in new list
        if (!empty($toRemove)) {
            $ph = implode(',', array_fill(0, count($toRemove), '?'));
            $del = $pdo->prepare("DELETE FROM recipe_categories WHERE recipe_id = ? AND category_id IN ($ph)");
            $del->bindValue(1, $id, PDO::PARAM_INT);
            foreach ($toRemove as $i => $cid) $del->bindValue($i+2, (int)$cid, PDO::PARAM_INT);
            $del->execute();
        }

        // Add new associations in provided order with sort_order
        if (!empty($toAdd)) {
            $ins = $pdo->prepare('INSERT INTO recipe_categories (recipe_id, category_id, is_primary, sort_order) VALUES (:rid, :cid, 0, :sort_order)');
            foreach ($categoryIds as $pos => $cid) {
                if (in_array((int)$cid, $toAdd, true)) {
                    $ins->bindValue(':rid', $id, PDO::PARAM_INT);
                    $ins->bindValue(':cid', (int)$cid, PDO::PARAM_INT);
                    $ins->bindValue(':sort_order', $pos, PDO::PARAM_INT);
                    $ins->execute();
                }
            }
        }

        // Update sort_order for kept items to match new order
        if (!empty($toKeep) || !empty($categoryIds)) {
            $upd = $pdo->prepare('UPDATE recipe_categories SET sort_order = :sort_order WHERE recipe_id = :rid AND category_id = :cid');
            foreach ($categoryIds as $pos => $cid) {
                $upd->bindValue(':sort_order', $pos, PDO::PARAM_INT);
                $upd->bindValue(':rid', $id, PDO::PARAM_INT);
                $upd->bindValue(':cid', (int)$cid, PDO::PARAM_INT);
                $upd->execute();
            }
        }

        // Primary handling: if existing primary still present in new list, keep it; else set first in list (if any) as primary; if list empty clear primaries
        $currentPrimary = null;
        foreach ($existing as $r) if ($r['is_primary']) { $currentPrimary = (int)$r['category_id']; break; }

        if (empty($categoryIds)) {
            // clear all primaries
            $clear = $pdo->prepare('UPDATE recipe_categories SET is_primary = 0 WHERE recipe_id = :rid');
            $clear->bindValue(':rid', $id, PDO::PARAM_INT);
            $clear->execute();
        } else {
            if (in_array($currentPrimary, $categoryIds, true)) {
                // ensure only that one is primary
                $clear = $pdo->prepare('UPDATE recipe_categories SET is_primary = (category_id = :cid) WHERE recipe_id = :rid');
                $clear->bindValue(':cid', $currentPrimary, PDO::PARAM_INT);
                $clear->bindValue(':rid', $id, PDO::PARAM_INT);
                $clear->execute();
            } else {
                // set first as primary
                $first = (int)$categoryIds[0];
                $clear = $pdo->prepare('UPDATE recipe_categories SET is_primary = (category_id = :cid) WHERE recipe_id = :rid');
                $clear->bindValue(':cid', $first, PDO::PARAM_INT);
                $clear->bindValue(':rid', $id, PDO::PARAM_INT);
                $clear->execute();
            }
        }
    }

    // Return updated recipe
    $updated = find_recipe_by_id($pdo, $id);
    $ingStmt = $pdo->prepare('SELECT id, quantity_text, ingredient_text, sort_order FROM ingredients WHERE recipe_id = :rid ORDER BY sort_order');
    $ingStmt->bindValue(':rid', $id, PDO::PARAM_INT);
    $ingStmt->execute();
    $ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);
    $stepStmt = $pdo->prepare('SELECT id, instruction_text, sort_order FROM steps WHERE recipe_id = :rid ORDER BY sort_order');
    $stepStmt->bindValue(':rid', $id, PDO::PARAM_INT);
    $stepStmt->execute();
    $steps = $stepStmt->fetchAll(PDO::FETCH_ASSOC);

    respond(['recipe' => $updated, 'ingredients' => $ingredients, 'steps' => $steps], 200);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(['error' => 'Database error', 'detail' => $e->getMessage()], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
