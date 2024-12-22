<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1, 3]);

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.id, 
            c.titre, 
            c.description, 
            c.niveau, 
            u.nom AS formateur_nom,
            (SELECT COUNT(*) FROM inscriptions WHERE course_id = c.id AND status = 'accepte') as inscriptions,
            (SELECT COUNT(*) FROM inscriptions WHERE course_id = c.id AND user_id = ? AND (status = 'accepte' OR status = 'en_attente')) as user_enrolled
        FROM cours c
        JOIN users u ON c.formateur_id = u.id
        WHERE c.is_active = 1
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logError("Erreur de récupération des cours : " . $e->getMessage());
    displayUserError("Impossible de charger les cours. Réessayez plus tard.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parcourir les Cours</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Parcourir les Cours</h1>
            <p>Découvrez et inscrivez-vous aux formations disponibles</p>
        </div>

        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="courses.php">Mes Cours</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <?php 
        if (isset($_SESSION['success_message'])) {
            echo '<div class="success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }

        if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?>">
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
            <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <?php if (empty($courses)): ?>
            <div class="error">
                <p>Aucun cours n'est actuellement disponible.</p>
            </div>
        <?php else: ?>
            <div class="course-list">
                <?php foreach ($courses as $course): ?>
                    <div class="card">
                        <h2><?= htmlspecialchars($course['titre']) ?></h2>
                        <p><?= htmlspecialchars($course['description']) ?></p>
                        <p><strong>Niveau :</strong> <?= htmlspecialchars($course['niveau']) ?></p>
                        <p><strong>Formateur :</strong> <?= htmlspecialchars($course['formateur_nom']) ?></p>
                        <p><strong>Inscriptions :</strong> <?= $course['inscriptions'] ?></p>

                        <?php if ($course['user_enrolled'] > 0): ?>
                            <p class="text-warning">Déjà inscrit</p>
                        <?php else: ?>
                            <form action="enroll.php" method="POST">
                                <?php CSRFToken::insertTokenField(); ?>
                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                <button type="submit" class="btn">S'inscrire</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="text-center">
            <a href="dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>