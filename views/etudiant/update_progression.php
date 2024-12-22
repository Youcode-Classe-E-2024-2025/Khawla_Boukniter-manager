<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../models/progression.php';
require_once '../../includes/error_handler.php';

checkAccess([1, 3]); 

header('Content-Type: application/json');

try {

    $module_id = $_POST['module_id'] ?? null;
    $course_id = $_POST['course_id'] ?? null;
    $progression = $_POST['progression'] ?? null;

    if (!$module_id || !$course_id || $progression === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Données invalides']);
        exit;
    }

    $check_stmt = $pdo->prepare("
        SELECT id FROM inscriptions 
        WHERE user_id = ? AND course_id = ?
    ");
    $check_stmt->execute([$_SESSION['user']['id'], $course_id]);

    if (!$check_stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès non autorisé']);
        exit;
    }

    $progression_model = new Progression($pdo);

    $progression_model->startModule($_SESSION['user']['id'], $module_id, $course_id);

    $result = $progression_model->updateModuleProgression(
        $_SESSION['user']['id'], 
        $module_id, 
        $progression
    );

    if ($result) {

        $course_progression = $progression_model->getCourseProgression(
            $_SESSION['user']['id'], 
            $course_id
        );

        echo json_encode([
            'success' => true,
            'module_progression' => $progression,
            'course_progression' => $course_progression['avg_progression']
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Impossible de mettre à jour la progression']);
    }

} catch (Exception $e) {
    logError("Erreur de mise à jour de progression : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>
