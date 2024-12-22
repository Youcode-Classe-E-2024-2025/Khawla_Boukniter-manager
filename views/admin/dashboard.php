<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';

if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

$stmt_total = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt_total->fetch()['total'];

$stmt_formateurs = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role_id = 2");
$formateurs_count = $stmt_formateurs->fetch()['total'];

$stmt_etudiants = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role_id = 3");
$etudiants_count = $stmt_etudiants->fetch()['total'];
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https:">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .dashboard-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .dashboard-card h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .dashboard-card .big-number {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--secondary-color);
        }

        .recent-modules .module-preview {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .quick-actions {
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .action-buttons .btn i {
            margin-right: 10px;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-warning {
            background-color: #ffc107;
            color: white;
        }

        .action-buttons .btn:hover {
            opacity: 0.9;
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Tableau de bord Administrateur</h1>
            <p>Bienvenue, <?= htmlspecialchars($_SESSION['user']['prenom']) ?> !</p>
        </div>

        <div class="nav">
                <ul>
                    <li><a href="../../auth/logout.php">Déconnexion</a></li>
                </ul>
            </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <h3>Gestion des Utilisateurs</h3>
                    <p><?= $total_users ?> Utilisateurs</p>
                    <a href="users.php" class="btn btn-primary">Gérer</a>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="card-content">
                    <h3>Formateurs</h3>
                    <p><?= $formateurs_count ?> Formateurs</p>
                    <a href="users.php?role=2" class="btn btn-primary">Voir</a>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="card-content">
                    <h3>Étudiants</h3>
                    <p><?= $etudiants_count ?> Étudiants</p>
                    <a href="users.php?role=3" class="btn btn-primary">Voir</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>

</body>

</html>