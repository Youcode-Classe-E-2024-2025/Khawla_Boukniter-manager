<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';

checkAccess([1]);

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    $_SESSION['error'] = "ID d'utilisateur invalide.";
    header('Location: users.php');
    exit();
}

try {
    $pdo->beginTransaction();

    $check_stmt = $pdo->prepare("
        SELECT id, nom, prenom, role_id, email 
        FROM users 
        WHERE id = ? AND role_id != 1
    ");
    $check_stmt->execute([$user_id]);
    $user = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Utilisateur non trouvé ou impossible à supprimer.");
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    $delete_inscriptions_stmt = $pdo->prepare("
        DELETE FROM inscriptions 
        WHERE user_id = ?
    ");
    $delete_inscriptions_stmt->execute([$user_id]);

    $courses_stmt = $pdo->prepare("SELECT id FROM cours WHERE formateur_id = ?");
    $courses_stmt->execute([$user_id]);
    $courses = $courses_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($courses)) {
        $delete_modules_stmt = $pdo->prepare("
            DELETE FROM modules 
            WHERE course_id IN (" . implode(',', array_fill(0, count($courses), '?')) . ")
        ");
        $delete_modules_stmt->execute($courses);
    }

    if (!empty($courses)) {
        $delete_course_inscriptions_stmt = $pdo->prepare("
            DELETE FROM inscriptions 
            WHERE course_id IN (" . implode(',', array_fill(0, count($courses), '?')) . ")
        ");
        $delete_course_inscriptions_stmt->execute($courses);
    }

    $delete_courses_stmt = $pdo->prepare("
        DELETE FROM cours 
        WHERE formateur_id = ?
    ");
    $delete_courses_stmt->execute([$user_id]);

    $delete_user_stmt = $pdo->prepare("
        DELETE FROM users 
        WHERE id = ?
    ");
    $delete_user_stmt->execute([$user_id]);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $pdo->commit();

    $_SESSION['success'] = "Utilisateur supprimé avec succès.";
    header('Location: users.php');
    exit();

} catch (Exception $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
    header('Location: users.php');
    exit();
}
?>