<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';

checkAccess([1, 3]); 

$user_id = $_SESSION['user']['id'];

$stmt_courses = $pdo->prepare("
    SELECT COUNT(DISTINCT course_id) as total_courses
    FROM inscriptions
    WHERE user_id = ? AND status = 'accepte'
");
$stmt_courses->execute([$user_id]);
$course_stats = $stmt_courses->fetch();

$total_courses = $course_stats['total_courses'] ?? 0;

$stmt_recent_courses = $pdo->prepare("
    SELECT DISTINCT
        c.id, 
        c.titre, 
        c.description
    FROM cours c
    JOIN inscriptions i ON c.id = i.course_id
    WHERE i.user_id = ? AND i.status = 'accepte'
    ORDER BY i.date_inscription DESC
    LIMIT 3
");
$stmt_recent_courses->execute([$user_id]);
$recent_courses = $stmt_recent_courses->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord Étudiant</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>
<body>
    <div class="container">
        <h1>Tableau de Bord Étudiant</h1>
        <p>Bienvenue, <?= htmlspecialchars($_SESSION['user']['prenom']) ?> !</p>

        <div class="nav">
            <ul>
                <li><a href="browse_courses.php">Cours</a></li>
                <li><a href="courses.php">Mes Cours</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Cours Inscrits</h3>
                <div class="big-number"><?= $total_courses ?></div>
            </div>

            <div class="dashboard-card">
                <h3>Cours Récents</h3>
                <?php if (empty($recent_courses)): ?>
                    <p>Vous n'êtes pas encore inscrit à des cours.</p>
                <?php else: ?>
                    <?php foreach ($recent_courses as $course): ?>
                        <div class="course-item">
                            <h4><?= htmlspecialchars($course['titre']) ?></h4>
                            <p><?= htmlspecialchars($course['description']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne</p>
    </footer>
</body>
</html>