<?php
session_start();
// Hier komt later de PHP-logica om het specifieke recept op te halen uit de database
$recept_id = isset($_GET['id']) ? $_GET['id'] : 1;
$recept_naam = "Recept " . $recept_id;
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $recept_naam; ?> - Ontkoking</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Lato:wght@600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #F5F5DC;
            font-family: 'Lato', sans-serif;
            font-weight: 600; /* Semibold */
            color: #333333;
            line-height: 1.6;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600; /* Semibold */
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3CB371;
        }

        .logo {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .nav {
            display: flex;
            gap: 20px;
        }

        .nav a {
            color: #333333;
            text-decoration: none;
            font-family: 'Poppins', sans-serif; /* Poppins Semibold voor navigatie */
            font-weight: 600;
            transition: color 0.3s ease;
            padding: 10px 15px;
        }

        .nav a:hover {
            color: #3CB371;
        }

        /* Hamburger menu styles */
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 10px;
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background: #333333;
            margin: 3px 0;
            transition: 0.3s;
        }

        .mobile-nav {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            z-index: 1000;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 30px;
        }

        .mobile-nav.active {
            display: flex;
        }

        .mobile-nav a {
            color: #333333;
            text-decoration: none;
            font-family: 'Poppins', sans-serif; /* Poppins Semibold voor mobiele navigatie */
            font-weight: 600;
            font-size: 24px;
            padding: 15px 30px;
            transition: color 0.3s ease;
        }

        .mobile-nav a:hover {
            color: #3CB371;
        }

        .close-menu {
            position: absolute;
            top: 30px;
            right: 30px;
            background: none;
            border: none;
            font-size: 30px;
            cursor: pointer;
            color: #333333;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        .recept-detail {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 30px;
        }

        .recept-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .recept-titel {
            font-family: 'Poppins', sans-serif; /* Poppins Semibold */
            font-weight: 600;
            font-size: 36px;
            color: #333333;
            margin-bottom: 20px;
        }

        .recept-meta {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
            color: #666;
            font-family: 'Lato', sans-serif; /* Lato Semibold */
            font-weight: 600;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .recept-afbeelding {
            width: 100%;
            max-width: 600px;
            height: 400px;
            background: linear-gradient(135deg, #3CB371 0%, #3CB371 100%);
            margin: 0 auto 30px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            font-family: 'Lato', sans-serif; /* Lato Semibold */
            font-weight: 600;
        }

        .recept-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .ingredienten-section, .bereiding-section {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
        }

        .section-titel {
            font-family: 'Poppins', sans-serif; /* Poppins Semibold */
            font-weight: 600;
            font-size: 24px;
            color: #333333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3CB371;
        }

        .ingredienten-lijst {
            list-style: none;
            font-family: 'Lato', sans-serif; /* Lato Semibold */
            font-weight: 600;
        }

        .ingredienten-lijst li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .ingredienten-lijst li:last-child {
            border-bottom: none;
        }

        .bereiding-stappen {
            list-style: decimal;
            margin-left: 20px;
            font-family: 'Lato', sans-serif; /* Lato Semibold */
            font-weight: 600;
        }

        .bereiding-stappen li {
            padding: 10px 0;
        }

        .voedingswaarden {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .voedingswaarden-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            font-family: 'Lato', sans-serif; /* Lato Semibold */
            font-weight: 600;
        }

        .voedings-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .actie-knoppen {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif; /* Poppins Semibold voor knoppen */
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-favoriet {
            background-color: #FFD700;
            color: #333333;
        }

        .btn-favoriet:hover {
            background-color: #e6c200;
        }

        .btn-terug {
            background-color: #333333;
            color: white;
        }

        .btn-terug:hover {
            background-color: #555555;
        }

        .admin-knoppen {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: center;
        }

        .btn-verwijderen {
            background-color: #ff4444;
            color: white;
        }

        .btn-verwijderen:hover {
            background-color: #cc0000;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .nav {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            .recept-content {
                grid-template-columns: 1fr;
            }

            .recept-titel {
                font-size: 28px;
            }

            .recept-afbeelding {
                height: 300px;
            }

            .voedingswaarden-grid {
                grid-template-columns: 1fr;
            }

            .recept-meta {
                flex-direction: column;
                gap: 15px;
            }

            .actie-knoppen {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="logo">
            <img src="/images/logo.png" alt="Ontkoking Logo">
        </div>

        <!-- Desktop Navigation -->
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="recepten.php">Recepten</a>
            <a href="login.php">Inloggen</a>
            <a href="registreer.php">Registreren</a>
            <a href="profiel.php">Profiel</a>
        </nav>

        <!-- Hamburger Menu -->
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div class="mobile-nav" id="mobileNav">
        <button class="close-menu" id="closeMenu">&times;</button>
        <a href="index.php">Home</a>
        <a href="recepten.php">Recepten</a>
        <a href="login.php">Inloggen</a>
        <a href="registreer.php">Registreren</a>
        <a href="profiel.php">Profiel</a>
    </div>

    <!-- Recept Detail -->
    <div class="recept-detail">
        <div class="recept-header">
            <h1 class="recept-titel"><?php echo $recept_naam; ?></h1>
            <div class="recept-meta">
                <div class="meta-item">Bereidingstijd: 30 minuten</div>
                <div class="meta-item">Aantal personen: 4</div>
                <div class="meta-item">Moeilijkheid: Gemakkelijk</div>
            </div>
        </div>

        <div class="recept-afbeelding">
            Afbeelding van <?php echo $recept_naam; ?>
        </div>

        <div class="recept-content">
            <div class="ingredienten-section">
                <h2 class="section-titel">Ingrediënten</h2>
                <ul class="ingredienten-lijst">
                    <li>Ingrediënt 1 - 200g</li>
                    <li>Ingrediënt 2 - 1 stuk</li>
                    <li>Ingrediënt 3 - 2 eetlepels</li>
                    <li>Ingrediënt 4 - 300ml</li>
                    <li>Ingrediënt 5 - naar smaak</li>
                    <li>Ingrediënt 6 - 150g</li>
                    <li>Ingrediënt 7 - 1 teen</li>
                    <li>Ingrediënt 8 - 1 bos</li>
                </ul>
            </div>

            <div class="bereiding-section">
                <h2 class="section-titel">Bereidingswijze</h2>
                <ol class="bereiding-stappen">
                    <li>Stap 1: Beschrijving van de eerste stap in het bereidingsproces.</li>
                    <li>Stap 2: Beschrijving van de tweede stap in het bereidingsproces.</li>
                    <li>Stap 3: Beschrijving van de derde stap in het bereidingsproces.</li>
                    <li>Stap 4: Beschrijving van de vierde stap in het bereidingsproces.</li>
                    <li>Stap 5: Beschrijving van de vijfde stap in het bereidingsproces.</li>
                    <li>Stap 6: Beschrijving van de laatste stap in het bereidingsproces.</li>
                </ol>
            </div>
        </div>

        <div class="voedingswaarden">
            <h2 class="section-titel">Voedingswaarden</h2>
            <div class="voedingswaarden-grid">
                <div class="voedings-item">
                    <span>Calorieën</span>
                    <span>350 kcal</span>
                </div>
                <div class="voedings-item">
                    <span>Eiwitten</span>
                    <span>25g</span>
                </div>
                <div class="voedings-item">
                    <span>Koolhydraten</span>
                    <span>45g</span>
                </div>
                <div class="voedings-item">
                    <span>Vetten</span>
                    <span>10g</span>
                </div>
                <div class="voedings-item">
                    <span>Suikers</span>
                    <span>8g</span>
                </div>
                <div class="voedings-item">
                    <span>Vezels</span>
                    <span>6g</span>
                </div>
            </div>
        </div>

        <!-- Actie knoppen -->
        <div class="actie-knoppen">
            <button class="btn btn-favoriet">Toevoegen aan favorieten</button>
            <a href="recepten.php" class="btn btn-terug">Terug naar recepten</a>
        </div>

        <!-- Admin knoppen (alleen zichtbaar voor admin) -->
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <div class="admin-knoppen">
                <button class="btn btn-verwijderen">Recept verwijderen</button>
                <button class="btn" style="background-color: #3CB371; color: white;">Recept bewerken</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Hamburger menu functionaliteit
    const hamburger = document.getElementById('hamburger');
    const mobileNav = document.getElementById('mobileNav');
    const closeMenu = document.getElementById('closeMenu');

    hamburger.addEventListener('click', function() {
        mobileNav.classList.add('active');
    });

    closeMenu.addEventListener('click', function() {
        mobileNav.classList.remove('active');
    });

    // Sluit menu wanneer er op een link geklikt wordt
    const mobileLinks = mobileNav.querySelectorAll('a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', function() {
            mobileNav.classList.remove('active');
        });
    });

    // Simpele JavaScript voor favorieten functionaliteit
    document.querySelector('.btn-favoriet').addEventListener('click', function() {
        this.textContent = this.textContent === 'Toevoegen aan favorieten'
            ? 'Toegevoegd aan favorieten'
            : 'Toevoegen aan favorieten';
    });

    // Verwijder knop bevestiging
    const verwijderKnop = document.querySelector('.btn-verwijderen');
    if (verwijderKnop) {
        verwijderKnop.addEventListener('click', function() {
            if (confirm('Weet je zeker dat je dit recept wilt verwijderen?')) {
                alert('Recept wordt verwijderd...');
                // Hier zou de verwijder-logica komen
            }
        });
    }
</script>
</body>
</html>