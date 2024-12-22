<?php
// Fonctions utilitaires génériques

// Journalisation des erreurs
function logError($message) {
    error_log($message, 3, __DIR__ . '/../logs/error.log');
}

// Validation des entrées
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Génération de messages flash
function setFlashMessage($type, $message) {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Affichage des messages flash
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = $_SESSION['flash_message']['message'];
        echo "<div class='alert alert-{$type}'>{$message}</div>";
        unset($_SESSION['flash_message']);
    }
}

// Redirection sécurisée
function redirectTo($url) {
    header("Location: $url");
    exit();
}
?>
