<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1, 2]); 

if (!CSRFToken::verifyToken($_GET['csrf_token'])) {
    $_SESSION['error'] = "Token CSRF invalide. Action non autorisée.";
    header('Location: modules.php');
    exit();
}

$user = $_SESSION['user'];

$module_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($module_id <= 0) {
    $_SESSION['error'] = "ID de module invalide.";
    header('Location: modules.php');
    exit();
}

try {

    $check_stmt = $pdo->prepare("
        SELECT m.id, c.id as course_id 
        FROM modules m
        JOIN cours c ON m.course_id = c.id
        WHERE m.id = ? 
        AND c.formateur_id = ? 
        AND m.is_archived = true
        AND c.is_active = true
    ");
    $check_stmt->execute([$module_id, $user['id']]);
    $module_info = $check_stmt->fetch();

    if (!$module_info) {
        $_SESSION['error'] = "Vous n'avez pas le droit d'activer ce module.";
        header('Location: modules.php');
        exit();
    }

    $activate_stmt = $pdo->prepare("
        UPDATE modules 
        SET is_archived = false 
        WHERE id = ? 
    ");
    $activate_stmt->execute([$module_id]);

    $_SESSION['success'] = "Le module a été activé avec succès.";
    header('Location: modules.php');
    exit();

} catch (PDOException $e) {
    logError("Erreur d'activation du module : " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de l'activation du module.";
    header('Location: modules.php');
    exit();
}
?>
