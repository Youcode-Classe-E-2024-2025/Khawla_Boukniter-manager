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

function logError($message, $context = []) {
    $logMessage = $message;
    if (!empty($context)) {
        $logMessage .= " | " . json_encode($context);
    }
    error_log($logMessage);
}
?>