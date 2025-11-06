<?php
session_start();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen Ontkoking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 400px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #4a90e2;
            outline: none;
        }
        
        .login-button {
            width: 100%;
            padding: 12px;
            background-color: #4a90e2;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .login-button:hover {
            background-color: #3a7bc8;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #555;
        }
        
        .register-link a {
            color: #4a90e2;
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>INLOGGEN</h1>
        
        <form method="POST" action="verwerk-login.php">
            <div class="form-group">
                <label for="gebruikersnaam">Gebruikersnaam</label>
                <input type="text" id="gebruikersnaam" name="gebruikersnaam" required>
            </div>
            
            <div class="form-group">
                <label for="wachtwoord">Wachtwoord</label>
                <input type="password" id="wachtwoord" name="wachtwoord" required>
            </div>
            
            <button type="submit" class="login-button">Inloggen</button>
        </form>
        
        <div class="register-link">
            Nog geen account? <a href="registreer.php">Registreer hier</a>
        </div>
    </div>
</body>
</html>