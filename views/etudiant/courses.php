<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';

checkAccess([1, 3]); 

try {

    $stmt = $pdo->prepare("
        SELECT c.id, c.titre, c.description, c.niveau, f.nom AS formateur_nom 
        FROM cours c
        JOIN inscriptions i ON c.id = i.course_id
        JOIN users f ON c.formateur_id = f.id
        WHERE i.user_id = ? AND i.status = 'accepted'
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Erreur de récupération des cours : " . $e->getMessage());
    displayUserError("Impossible de charger vos cours. Réessayez plus tard.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Cours</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Mes Cours</h1>
            <p>Suivez votre progression dans les formations</p>
        </div>

        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="browse_courses.php">Parcourir les cours</a></li>
            </ul>
        </div>

        <?php 

        if (isset($_SESSION['success_message'])) {
            echo '<div class="success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }

        if (empty($courses)): ?>
            <div class="error">
                <p>Vous n'êtes inscrit à aucun cours pour le moment.</p>
            </div>
            <div class="text-center">
                <a href="browse_courses.php" class="btn">Parcourir les cours</a>
            </div>
        <?php else: ?>
            <div class="course-list">
                <?php foreach ($courses as $course): ?>
                    <div class="card">
                        <h2><?= htmlspecialchars($course['titre']) ?></h2>
                        <p><?= htmlspecialchars($course['description']) ?></p>
                        <p><strong>Niveau :</strong> <?= htmlspecialchars($course['niveau']) ?></p>
                        
                        <?php 
                        $module_stmt = $pdo->prepare("
                            SELECT id, titre, description 
                            FROM modules 
                            WHERE course_id =?
                        ");
                        $module_stmt->execute([$course['id']]);
                        $modules = $module_stmt->fetchAll();
                        ?>
                        
                        <?php if (!empty($modules)): ?>
                            <div class="modules-section">
                                <h3>Modules</h3>
                                <?php foreach ($modules as $module): ?>
                                    <div class="module-item">
                                        <h4><?= htmlspecialchars($module['titre']) ?></h4>
                                        <p><?= htmlspecialchars($module['description']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>

<style>
.modules-section {
    margin-top: 20px;
}

.module-item {
    margin-bottom: 20px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.module-item h4 {
    margin-top: 0;
}

.module-item p {
    margin-bottom: 10px;
}
</style>