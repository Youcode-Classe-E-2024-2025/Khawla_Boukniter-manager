<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../models/progression.php';

checkAccess([1, 3]); 

$user_id = $_SESSION['user']['id'];
$progression_model = new Progression($pdo);

$stmt_courses = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT course_id) as total_courses,
        AVG(progression) as avg_progression
    FROM inscriptions
    WHERE user_id = ?
");
$stmt_courses->execute([$user_id]);
$course_stats = $stmt_courses->fetch();

$total_courses = $course_stats['total_courses'] ?? 0;
$avg_progression = round($course_stats['avg_progression'] ?? 0, 2);

$stmt_recent_courses = $pdo->prepare("
    SELECT 
        c.id, 
        c.titre, 
        i.progression,
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
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .dashboard-card {
            background-color: #f4f4f4;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .big-number {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--primary-color);
        }
        .progress-circle {
            width: 100px;
            height: 100px;
            background: conic-gradient(var(--primary-color) <?= $avg_progression ?>%, #e0e0e0 <?= $avg_progression ?>%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        .progress-circle span {
            background: white;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tableau de Bord</h1>
        <p>Bienvenue, <?= htmlspecialchars($_SESSION['user']['prenom']) ?> !</p>

        <div class="nav">
            <ul>
                <li><a href="browse_courses.php">cours</a></li>
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
                <h3>Progression Globale</h3>
                <div class="progress-circle">
                    <span><?= $avg_progression ?>%</span>
                </div>
            </div>

            <div class="dashboard-card">
                <h3>Cours Récents</h3>
                <?php foreach ($recent_courses as $course): ?>
                    <div class="course-item">
                        <h4><?= htmlspecialchars($course['titre']) ?></h4>
                        <div class="progress-bar">
                            <div style="width: <?= $course['progression'] ?>%"></div>
                        </div>
                        <p><?= number_format($course['progression'], 2) ?>% terminé</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne</p>
    </footer>
</body>
</html>