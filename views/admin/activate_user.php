<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1]);

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    $_SESSION['error'] = "ID d'utilisateur invalide.";

    exit();
}

try {

    $check_stmt = $pdo->prepare("
        SELECT id FROM users 
        WHERE id = ? AND is_active = false AND role_id != 1
    ");
    $check_stmt->execute([$user_id]);

    if (!$check_stmt->fetch()) {
        $_SESSION['error'] = "Vous ne pouvez pas activer cet utilisateur.";

        exit();
    }

    $activate_stmt = $pdo->prepare("
        UPDATE users 
        SET is_active = true 
        WHERE id = ?
    ");
    $activate_stmt->execute([$user_id]);

    $_SESSION['success'] = "L'utilisateur a été activé avec succès.";

    exit();

} catch (PDOException $e) {
    logError("Erreur d'activation de l'utilisateur : " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de l'activation de l'utilisateur.";

    exit();
}
?>
