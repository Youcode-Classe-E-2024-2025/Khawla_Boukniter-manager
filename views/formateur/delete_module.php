<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';

checkAccess([2, 1]); 

$user = $_SESSION['user'];

$module_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {

    $pdo->beginTransaction();

    $verif_stmt = $pdo->prepare("
        SELECT m.id, m.course_id, c.formateur_id 
        FROM modules m
        JOIN cours c ON m.course_id = c.id
        WHERE m.id = ?
    ");
    $verif_stmt->execute([$module_id]);
    $module = $verif_stmt->fetch();

    if (!$module || $module['formateur_id'] != $user['id']) {
        $_SESSION['message_erreur'] = "Vous n'avez pas le droit d'archiver ce module.";
        header("Location: modules.php");
        exit();
    }

    $archive_stmt = $pdo->prepare("
        UPDATE modules 
        SET is_archived = true 
        WHERE id = ?
    ");
    $archive_stmt->execute([$module_id]);

    $pdo->commit();

    $_SESSION['message_succes'] = "Module archivé avec succès.";
    header("Location: modules.php");
    exit();

} catch (PDOException $e) {

    $pdo->rollBack();

    logError("Erreur lors de l'archivage du module : " . $e->getMessage());
    $_SESSION['message_erreur'] = "Une erreur est survenue lors de l'archivage du module.";
    header("Location: modules.php");
    exit();
}