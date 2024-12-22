<?php session_start();

$is_admin = $_SESSION['is_admin'] ?? false;

$host = 'localhost';
$dbname = 'formation_manager';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    $stmt = $pdo->query("SELECT 1");
    if (!$stmt) {
        error_log("Impossible d'exécuter une requête de test sur la base de données");
    }
} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    error_log("Détails de l'erreur : " . print_r($e->errorInfo, true));
    die("Erreur de connexion : " . $e->getMessage());
}

function login($email, $password) {
    global $pdo;
    
    try {
        // Préparer la requête pour vérifier l'utilisateur
        $stmt = $pdo->prepare("
            SELECT id, email, password, role_id, is_active, is_banned, 
                   nom, prenom 
            FROM users 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Vérifier si l'utilisateur existe
        if (!$user) {
            return ['success' => false, 'message' => 'Identifiants incorrects.'];
        }

        // Vérifier le mot de passe
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Identifiants incorrects.'];
        }

        // Vérifier si le compte est actif
        if ($user['is_active'] != 1) {
            return ['success' => false, 'message' => 'Votre compte est désactivé.'];
        }

        // Vérifier si le compte est banni
        if ($user['is_banned'] == 1) {
            return ['success' => false, 'message' => 'Votre compte a été banni. Contactez l\'administrateur.'];
        }

        // Définir les informations de session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['is_admin'] = $user['role_id'] == 1;

        // Log de connexion réussie
        error_log("Connexion réussie pour {$user['email']} (ID: {$user['id']})");

        return ['success' => true, 'message' => 'Connexion réussie.'];

    } catch (PDOException $e) {
        // Log des erreurs
        error_log("Erreur de connexion : " . $e->getMessage());
        return ['success' => false, 'message' => 'Une erreur est survenue. Veuillez réessayer.'];
    }
}

function logError($message, $context = []) {
    $logMessage = $message;
    if (!empty($context)) {
        $logMessage .= " | " . json_encode($context);
    }
    error_log($logMessage);
}
?>