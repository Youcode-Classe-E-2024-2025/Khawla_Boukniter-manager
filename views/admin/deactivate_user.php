<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1]);

if (!CSRFToken::verifyToken($_GET['csrf_token'])) {
    $_SESSION['error'] = "Token CSRF invalide. Action non autorisée.";
    header('Location: users.php');
    exit();
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    $_SESSION['error'] = "ID d'utilisateur invalide.";
    header('Location: users.php');
    exit();
}

try {

    $check_stmt = $pdo->prepare("
        SELECT id FROM users 
        WHERE id = ? AND is_active = true AND role_id != 1
    ");
    $check_stmt->execute([$user_id]);

    if (!$check_stmt->fetch()) {
        $_SESSION['error'] = "Vous ne pouvez pas désactiver cet utilisateur.";
        header('Location: users.php');
        exit();
    }

    $deactivate_stmt = $pdo->prepare("
        UPDATE users 
        SET is_active = false 
        WHERE id = ?
    ");
    $deactivate_stmt->execute([$user_id]);

    $_SESSION['success'] = "L'utilisateur a été désactivé avec succès.";
    header('Location: users.php');
    exit();

} catch (PDOException $e) {
    logError("Erreur de désactivation de l'utilisateur : " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la désactivation de l'utilisateur.";
    header('Location: users.php');
    exit();
}
?>
