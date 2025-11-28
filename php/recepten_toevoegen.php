<?php
session_start();
require_once __DIR__ . '/../api/app_helpers.php';
// Force login: redirect to login page if not logged in
require_login();
$currentUserId = get_current_user_id();
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Recept toevoegen</title>
  <link rel="stylesheet" href="/css-pagina-specific/home.css">
  <link rel="stylesheet" href="/css-pagina-specific/recepten_toevoegen.css">
</head>
<body class="page">
  <header class="top-bar">
    <div class="container">
      <a class="logo" href="/paginas/home.html">
        <img src="/images/fotos/logo.png" alt="logo" class="logo-img" />
      </a>
      <nav class="main-nav" aria-label="Hoofdmenu">
        <a href="/paginas/home.html">Home</a>
        <a href="/paginas/recepten.html">Recepten</a>
        <a href="/paginas/registreer.html">Registreer</a>
      </nav>
      <div class="actions">
        <a class="btn primary" href="/paginas/recepten.html">Bekijk recepten</a>
      </div>
    </div>
  </header>

  <main class="container" style="padding:2rem 0">
    <h1>Voeg een recept toe</h1>
    <p>Gebruik het formulier hieronder om een nieuw recept te maken. Velden met * zijn verplicht.</p>

    <form id="recipe-form">
      <div class="form-grid">
        <label>Gebruiker ID (automatisch ingevuld omdat je bent ingelogd):
          <input type="text" id="user_id" name="user_id" value="<?= htmlspecialchars($currentUserId) ?>" placeholder="user id" readonly />
        </label>

        <label>Categorie:
          <select id="category_id" name="category_id">
            <option value="">-- Kies categorie (of laat leeg voor nieuwe) --</option>
          </select>
        </label>

        <label>Of nieuwe categorie naam:
          <input type="text" id="category_name" name="category" placeholder="Bijv. Ontbijt" />
        </label>

        <label>Titel *:
          <input type="text" id="title" name="title" required />
        </label>

        <label>Omschrijving:
          <textarea id="description" name="description" rows="4"></textarea>
        </label>

        <label>Porties:
          <input type="number" id="servings" name="servings" min="1" />
        </label>

        <label>Voorbereiding (min):
          <input type="number" id="prep_time_minutes" name="prep_time_minutes" min="0" />
        </label>

        <label>Koken (min):
          <input type="number" id="cook_time_minutes" name="cook_time_minutes" min="0" />
        </label>

        <label>Moeilijkheid:
          <select id="difficulty" name="difficulty">
            <option value="">-- Kies --</option>
            <option value="easy">Easy</option>
            <option value="medium">Medium</option>
            <option value="hard">Hard</option>
          </select>
        </label>

        <label class="checkbox-inline">Gepubliceerd:
          <input type="checkbox" id="is_published" name="is_published" />
        </label>
      </div>

      <section>
        <h2>Ingrediënten</h2>
        <div id="ingredients-list"></div>
        <button type="button" id="add-ingredient" class="btn secondary">+ Ingrediënt toevoegen</button>
      </section>

      <section>
        <h2>Stappen</h2>
        <div id="steps-list"></div>
        <button type="button" id="add-step" class="btn secondary">+ Stap toevoegen</button>
      </section>

      <div style="margin-top:1rem">
        <button type="submit" class="btn primary">Recept aanmaken</button>
      </div>
    </form>

    <div id="message" aria-live="polite" style="margin-top:1rem"></div>
  </main>

  <footer class="footer">
    <div class="container">Stop de ontkoking — &copy; <?= date('Y') ?></div>
  </footer>

  <script>
  // Helper to create ingredient row
  function createIngredientRow(i, quantity = '', text = '') {
    const wrapper = document.createElement('div');
    wrapper.className = 'row-item';
    wrapper.innerHTML = `
      <input class="small" data-idx="${i}" name="ing_quantity_${i}" placeholder="Aantal / hoeveelheid" value="${escapeHtml(quantity)}" />
      <input data-idx="${i}" name="ing_text_${i}" placeholder="Ingrediënt (bijv. 150g bloem)" value="${escapeHtml(text)}" />
      <button type="button" class="btn remove small">Verwijder</button>
    `;
    wrapper.querySelector('.remove').addEventListener('click', ()=> wrapper.remove());
    return wrapper;
  }

  function createStepRow(i, text = '') {
    const wrapper = document.createElement('div');
    wrapper.className = 'row-item';
    wrapper.innerHTML = `
      <textarea data-idx="${i}" name="step_text_${i}" rows="2" placeholder="Stapbeschrijving">${escapeHtml(text)}</textarea>
      <button type="button" class="btn remove small">Verwijder</button>
    `;
    wrapper.querySelector('.remove').addEventListener('click', ()=> wrapper.remove());
    return wrapper;
  }

  function escapeHtml(s) { return (s+'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  document.addEventListener('DOMContentLoaded', () => {
    const ingredientsList = document.getElementById('ingredients-list');
    const stepsList = document.getElementById('steps-list');
    const addIngredientBtn = document.getElementById('add-ingredient');
    const addStepBtn = document.getElementById('add-step');
    const form = document.getElementById('recipe-form');
    const message = document.getElementById('message');

    // Start with two empty rows
    ingredientsList.appendChild(createIngredientRow(0));
    ingredientsList.appendChild(createIngredientRow(1));
    stepsList.appendChild(createStepRow(0));

    addIngredientBtn.addEventListener('click', ()=> {
      const idx = ingredientsList.children.length ? Array.from(ingredientsList.children).length : 0;
      ingredientsList.appendChild(createIngredientRow(idx));
    });

    addStepBtn.addEventListener('click', ()=> {
      const idx = stepsList.children.length ? Array.from(stepsList.children).length : 0;
      stepsList.appendChild(createStepRow(idx));
    });

    // Load categories
    fetch('/api/categories/get_category.php')
      .then(r => r.json())
      .then(j => {
        if (j.categories) {
          const sel = document.getElementById('category_id');
          j.categories.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            sel.appendChild(opt);
          });
        }
      }).catch(()=>{/* ignore */});

    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      message.textContent = '';

      const userId = document.getElementById('user_id').value.trim();
      if (!userId) {
        message.textContent = 'Voer een geldige gebruiker ID in of log in.';
        return;
      }

      const title = document.getElementById('title').value.trim();
      if (!title) { message.textContent = 'Titel is verplicht.'; return; }

      const payload = {
        user_id: parseInt(userId, 10),
        category_id: document.getElementById('category_id').value ? parseInt(document.getElementById('category_id').value, 10) : null,
        category: document.getElementById('category_name').value.trim() || null,
        title: title,
        description: document.getElementById('description').value.trim() || null,
        servings: parseInt(document.getElementById('servings').value, 10) || null,
        prep_time_minutes: parseInt(document.getElementById('prep_time_minutes').value, 10) || null,
        cook_time_minutes: parseInt(document.getElementById('cook_time_minutes').value, 10) || null,
        difficulty: document.getElementById('difficulty').value || null,
        is_published: document.getElementById('is_published').checked ? 1 : 0,
        ingredients: [],
        steps: []
      };

      // Gather ingredients
      Array.from(document.querySelectorAll('#ingredients-list .row-item')).forEach((el, i) => {
        const q = el.querySelector('input[name^="ing_quantity_"]')?.value || null;
        const t = el.querySelector('input[name^="ing_text_"]')?.value || '';
        if (t.trim() !== '') payload.ingredients.push({ quantity_text: q, ingredient_text: t.trim(), sort_order: i });
      });

      // Gather steps
      Array.from(document.querySelectorAll('#steps-list .row-item')).forEach((el, i) => {
        const t = el.querySelector('textarea[name^="step_text_"]')?.value || '';
        if (t.trim() !== '') payload.steps.push({ instruction_text: t.trim(), sort_order: i });
      });

      try {
        const res = await fetch('/api/recipes/add_recipe.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.status >= 200 && res.status < 300) {
          message.textContent = 'Recept succesvol aangemaakt (ID: ' + (data.recipe?.id ?? '') + ').';
          form.reset();
          // reset ingredient/steps
          ingredientsList.innerHTML = '';
          stepsList.innerHTML = '';
          ingredientsList.appendChild(createIngredientRow(0));
          stepsList.appendChild(createStepRow(0));
        } else {
          message.textContent = (data.error || JSON.stringify(data));
        }
      } catch (err) {
        message.textContent = 'Verzendfout: ' + err.message;
      }
    });
  });
  </script>
</body>
</html>
