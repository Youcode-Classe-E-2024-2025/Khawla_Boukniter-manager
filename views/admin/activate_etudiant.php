<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1]);

if (!CSRFToken::verifyToken($_GET['csrf_token'])) {
    $_SESSION['error'] = "Token CSRF invalide. Action non autorisée.";
    header('Location: etudiants.php');
    exit();
}

$etudiant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($etudiant_id <= 0) {
    $_SESSION['error'] = "ID d'étudiant invalide.";
    header('Location: etudiants.php');
    exit();
}

try {

    $check_stmt = $pdo->prepare("
        SELECT id FROM users 
        WHERE id = ? AND role_id = 3 AND is_active = false
    ");
    $check_stmt->execute([$etudiant_id]);

    if (!$check_stmt->fetch()) {
        $_SESSION['error'] = "Vous ne pouvez pas activer cet étudiant.";
        header('Location: etudiants.php');
        exit();
    }

    $activate_stmt = $pdo->prepare("
        UPDATE users 
        SET is_active = true 
        WHERE id = ?
    ");
    $activate_stmt->execute([$etudiant_id]);

    $_SESSION['success'] = "L'étudiant a été activé avec succès.";
    header('Location: etudiants.php');
    exit();

} catch (PDOException $e) {
    logError("Erreur d'activation de l'étudiant : " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de l'activation de l'étudiant.";
    header('Location: etudiants.php');
    exit();
}
?>
