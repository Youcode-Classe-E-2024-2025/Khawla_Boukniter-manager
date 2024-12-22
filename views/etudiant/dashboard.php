<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';

checkAccess([1, 3]); 

$user_id = $_SESSION['user']['id'];

$stmt_courses = $pdo->prepare("
    SELECT COUNT(DISTINCT course_id) as total_courses
    FROM inscriptions
    WHERE user_id = ?
");
$stmt_courses->execute([$user_id]);
$course_stats = $stmt_courses->fetch();

$total_courses = $course_stats['total_courses'] ?? 0;

$stmt_recent_courses = $pdo->prepare("
    SELECT 
        c.id, 
        c.titre, 
        c.description
    FROM cours c
    JOIN inscriptions i ON c.id = i.course_id
    WHERE i.user_id = ?
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
</head>
<body>
    <div class="dashboard-container">
        <h1>Tableau de Bord Étudiant</h1>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Cours Inscrits</h3>
                <p><?= $total_courses ?></p>
            </div>
        </div>

        <div class="recent-courses">
            <h2>Cours Récents</h2>
            <?php if (empty($recent_courses)): ?>
                <p>Vous n'êtes pas encore inscrit à des cours.</p>
            <?php else: ?>
                <?php foreach ($recent_courses as $course): ?>
                    <div class="course-card">
                        <h3><?= htmlspecialchars($course['titre']) ?></h3>
                        <p><?= htmlspecialchars($course['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>