<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1, 2]); 

if (!CSRFToken::verifyToken($_GET['csrf_token'])) {
    $_SESSION['error'] = "Token CSRF invalide. Action non autorisée.";
    header('Location: courses.php');
    exit();
}

$user = $_SESSION['user'];

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    $_SESSION['error'] = "ID de cours invalide.";
    header('Location: courses.php');
    exit();
}

try {

    $check_stmt = $pdo->prepare("
        SELECT id FROM cours 
        WHERE id = ? AND formateur_id = ? AND is_active = false
    ");
    $check_stmt->execute([$course_id, $user['id']]);

    if (!$check_stmt->fetch()) {
        $_SESSION['error'] = "Vous n'avez pas le droit d'activer ce cours.";
        header('Location: courses.php');
        exit();
    }

    $activate_stmt = $pdo->prepare("
        UPDATE cours 
        SET is_active = true 
        WHERE id = ? AND formateur_id = ?
    ");
    $activate_stmt->execute([$course_id, $user['id']]);

    $modules_stmt = $pdo->prepare("
        UPDATE modules 
        SET is_archived = false 
        WHERE course_id = ?
    ");
    $modules_stmt->execute([$course_id]);

    $_SESSION['success'] = "Le cours a été activé avec succès.";
    header('Location: courses.php');
    exit();

} catch (PDOException $e) {
    logError("Erreur d'activation du cours : " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de l'activation du cours.";
    header('Location: courses.php');
    exit();
}
?>
