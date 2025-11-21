<?php
session_start();
require_once '../api/config.php';

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the raw POST data (assuming JSON)
$input = json_decode(file_get_contents('php://input'), true);

// If not JSON, try standard POST (form-data)
if (!$input) {
    $input = $_POST;
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Vul alle velden in.']);
    exit;
}

try {
    $pdo = get_pdo();
    
    // Prepare statement to find user by username or email
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = :username OR email = :email LIMIT 1");
    $stmt->execute([':username' => $username, ':email' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Password is correct
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        echo json_encode([
            'success' => true, 
            'message' => 'Succesvol ingelogd!',
            'redirect' => 'index.html' // Or dashboard, etc.
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ongeldige gebruikersnaam of wachtwoord.']);
    }

} catch (PDOException $e) {
    // Log error in a real app
    echo json_encode(['success' => false, 'message' => 'Database fout: ' . $e->getMessage()]);
}
