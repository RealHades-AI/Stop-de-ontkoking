# Ingredients API

Standalone endpoints to manage `ingredients`. Note: recipes endpoints already allow creating and replacing lists of ingredients when creating or editing a recipe. These endpoints are for single-ingredient operations.

Files

- `add_ingredient.php` — Create a single ingredient. Requires `recipe_id` and `ingredient_text`.
- `get_ingredients.php` — Fetch a single ingredient by `id` or list by `recipe_id` with pagination.
- `edit_ingredient.php` — Update fields (`quantity_text`, `ingredient_text`, `sort_order`) for an ingredient by `id`.
- `delete_ingredient.php` — Delete an ingredient by `id`.

Usage examples

- Add:
  { "recipe_id": 12, "quantity_text": "1 cup", "ingredient_text": "flour", "sort_order": 0 }
- Get list:
  GET `get_ingredients.php?recipe_id=12&page=1&per_page=50`
- Edit:
  { "id": 34, "ingredient_text": "all-purpose flour", "sort_order": 1 }
- Delete:
  { "id": 34 }
