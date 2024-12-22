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
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
            color: var(--primary-color, #007bff);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .nav ul li a {
            text-decoration: none;
        }
        .course-item {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            padding: 10px;
            background-color: #f4f4f4;
        }
    </style>
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