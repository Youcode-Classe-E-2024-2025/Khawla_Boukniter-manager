<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';

checkAccess([1, 2]);

$user = $_SESSION['user'];
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if ($student_id <= 0) {
    $_SESSION['message_erreur'] = "ID étudiant invalide.";
    header("Location: students.php");
    exit();
}

try {
    $check_stmt = $pdo->prepare("
        SELECT COUNT(*) as course_count
        FROM inscriptions i
        JOIN cours c ON i.course_id = c.id
        WHERE i.user_id = ? AND c.formateur_id = ?
    ");
    $check_stmt->execute([$student_id, $user['id']]);
    $result = $check_stmt->fetch();

    if ($result['course_count'] == 0) {
        $_SESSION['message_erreur'] = "Vous ne pouvez pas supprimer cet étudiant.";
        header("Location: students.php");
        exit();
    }

    $delete_stmt = $pdo->prepare("
        DELETE FROM inscriptions
        WHERE user_id = ? AND course_id IN (
            SELECT id FROM cours WHERE formateur_id = ?
        )
    ");
    $delete_stmt->execute([$student_id, $user['id']]);

    $_SESSION['message_succes'] = "L'étudiant a été supprimé de tous vos cours.";
    header("Location: students.php");
    exit();

} catch (PDOException $e) {
    logError("Erreur de suppression de l'étudiant : " . $e->getMessage());
    $_SESSION['message_erreur'] = "Une erreur est survenue lors de la suppression de l'étudiant.";
    header("Location: students.php");
    exit();
}
?>
