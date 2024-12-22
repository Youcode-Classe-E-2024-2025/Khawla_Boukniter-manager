<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1, 3]); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        CSRFToken::verifyToken($_POST['csrf_token']);

        if (!isset($_POST['course_id'])) {
            displayUserError("Aucun cours sélectionné.");
        }

        $course_id = intval($_POST['course_id']);
        $user_id = $_SESSION['user']['id'];

        $course_stmt = $pdo->prepare("SELECT titre, description, niveau FROM cours WHERE id = ? AND is_active = 1");
        $course_stmt->execute([$course_id]);
        $course = $course_stmt->fetch();

        if (!$course) {
            displayUserError("Cours non trouvé ou inactif.");
        }

        $check_stmt = $pdo->prepare("
            SELECT * FROM inscriptions 
            WHERE user_id = ? AND course_id = ? 
            AND (status = 'en_attente' OR status = 'accepte')
        ");
        $check_stmt->execute([$user_id, $course_id]);

        if ($check_stmt->rowCount() > 0) {
            displayUserError("Vous avez déjà une inscription en cours ou êtes inscrit à ce cours.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO inscriptions (user_id, course_id, progression, status) 
            VALUES (?, ?, 0, 'en_attente')
        ");
        $result = $stmt->execute([$user_id, $course_id]);

        if ($result) {
            $_SESSION['enrolled_course'] = $course;
            $_SESSION['success_message'] = "Votre demande d'inscription a été envoyée. En attente de validation par le formateur.";
        } else {
            displayUserError("Erreur lors de l'envoi de la demande d'inscription.");
        }
    } catch (Exception $e) {
        logError("Erreur d'inscription au cours : " . $e->getMessage());
        displayUserError("Une erreur est survenue lors de l'inscription.");
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation d'inscription</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Inscription au Cours</h1>
        </div>

        <?php 

        if (isset($_SESSION['success_message'])) {
            echo '<div class="success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }

        if (isset($_SESSION['enrolled_course'])): 
            $course = $_SESSION['enrolled_course'];
            unset($_SESSION['enrolled_course']);
        ?>
            <div class="card">
                <h2>Détails du Cours</h2>
                <p><strong>Titre :</strong> <?= htmlspecialchars($course['titre']) ?></p>
                <p><strong>Description :</strong> <?= htmlspecialchars($course['description']) ?></p>
                <p><strong>Niveau :</strong> <?= htmlspecialchars($course['niveau']) ?></p>
                <p><strong>Statut :</strong> En attente de validation</p>
            </div>

            <div class="text-center">
                <a href="courses.php" class="btn">Voir mes cours</a>
                <a href="browse_courses.php" class="btn btn-secondary">Parcourir d'autres cours</a>
            </div>
        <?php else: ?>
            <div class="error">
                <p>Aucune inscription en cours.</p>
            </div>
            <div class="text-center">
                <a href="browse_courses.php" class="btn">Retour à la liste des cours</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>