# Recipe Categories API

This folder contains endpoints to manage the many-to-many relationship between recipes and categories.

Files

- `add_recipe_category.php` — Attach or upsert a category to a recipe. Accepts `recipe_id`, `category_id`, optional `is_primary` (bool) and `sort_order` (int). When `is_primary` is true, other associations for the recipe will have `is_primary` cleared.
- `get_recipe_categories.php` — Fetch a single association (by recipe_id + category_id) or list associations filtered by `recipe_id` or `category_id` with pagination (`page`, `per_page`).
- `edit_recipe_category.php` — Update `is_primary` and/or `sort_order` for an existing association. If `is_primary` set true, clears others.
- `delete_recipe_category.php` — Remove an association by `recipe_id` and `category_id`.

Usage examples (HTTP POST JSON)

Add association:
{
"recipe_id": 12,
"category_id": 3,
"is_primary": true,
"sort_order": 0
}

Get associations:

- GET `get_recipe_categories.php?recipe_id=12`
- GET `get_recipe_categories.php?category_id=3`
- GET `get_recipe_categories.php?page=1&per_page=50`

Edit association:
{
"recipe_id": 12,
"category_id": 3,
"is_primary": false,
"sort_order": 2
}

Delete association:
{
"recipe_id": 12,
"category_id": 3
}

Notes

- The table `recipe_categories` uses a composite primary key `(recipe_id, category_id)`. `add_recipe_category.php` uses an upsert (INSERT ... ON DUPLICATE KEY UPDATE) to avoid duplicate associations.
- `is_primary` is enforced per-recipe: only one associated category should have `is_primary = 1`.
