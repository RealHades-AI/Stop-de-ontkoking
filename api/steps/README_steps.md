# Steps API

Standalone endpoints to manage `steps`. Note: recipes endpoints already allow creating and replacing lists of steps when creating or editing a recipe. These endpoints are for single-step operations.

Files

- `add_step.php` — Create a single step. Requires `recipe_id` and `instruction_text`.
- `get_steps.php` — Fetch a single step by `id` or list by `recipe_id` with pagination.
- `edit_step.php` — Update fields (`instruction_text`, `sort_order`) for a step by `id`.
- `delete_step.php` — Delete a step by `id`.

Usage examples

- Add:
  { "recipe_id": 12, "instruction_text": "Mix ingredients", "sort_order": 0 }
- Get list:
  GET `get_steps.php?recipe_id=12&page=1&per_page=50`
- Edit:
  { "id": 45, "instruction_text": "Whisk until smooth", "sort_order": 2 }
- Delete:
  { "id": 45 }
