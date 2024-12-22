<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';

checkAccess([1, 2]); 

$user = $_SESSION['user'];

try {

    $total_modules_stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM modules m
        JOIN cours c ON m.course_id = c.id
        WHERE c.formateur_id = ? AND c.is_active = true AND m.is_archived = false
    ");
    $total_modules_stmt->execute([$user['id']]);
    $total_modules = $total_modules_stmt->fetch()['total'];

    $inscriptions_stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT i.id) as total_inscriptions 
        FROM inscriptions i
        JOIN cours c ON i.course_id = c.id
        WHERE c.formateur_id = ? AND i.status = 'accepte'
    ");
    $inscriptions_stmt->execute([$user['id']]);
    $total_inscriptions = $inscriptions_stmt->fetch(PDO::FETCH_ASSOC)['total_inscriptions'];

    $pending_inscriptions_stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT i.id) as pending_inscriptions 
        FROM inscriptions i
        JOIN cours c ON i.course_id = c.id
        WHERE c.formateur_id = ? AND i.status = 'en_attente'
    ");
    $pending_inscriptions_stmt->execute([$user['id']]);
    $pending_inscriptions = $pending_inscriptions_stmt->fetch(PDO::FETCH_ASSOC)['pending_inscriptions'];

    $total_courses_stmt = $pdo->prepare("
        SELECT COUNT(*) as total_courses 
        FROM cours 
        WHERE formateur_id = ? AND is_active = true
    ");
    $total_courses_stmt->execute([$user['id']]);
    $total_courses = $total_courses_stmt->fetch()['total_courses'];
} catch (PDOException $e) {
    logError("Erreur de récupération des données : " . $e->getMessage());
    $total_modules = 0;
    $total_inscriptions = 0;
    $pending_inscriptions = 0;
    $total_courses = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Formateur</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/sweet_alerts.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />    
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
        
        .badge {
            background-color: #ff9800;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            position: absolute;
            top: 20px;
            right: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Tableau de Bord Formateur</h1>
            <p>Bienvenue, <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
        </div>

        <div class="nav">
            <ul>
                <li><a href="students.php">Mes Étudiants</a></li>
                <li><a href="manage_inscriptions.php">Gestion des Inscriptions</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <div class="quick-actions">
            <div class="action-buttons">
                <a href="create_course.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Créer un Cours
                </a>
                <a href="courses.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Mes Cours
                </a>
                <a href="create_module.php" class="btn btn-info">
                    <i class="fas fa-book-open"></i> Ajouter un Module
                </a>
                <a href="modules.php" class="btn btn-warning">
                    <i class="fas fa-book"></i> Gérer les Modules
                </a>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Modules Créés</h3>
                <div class="big-number"><?= $total_modules ?></div>
                <p>Total des modules</p>
            </div>

            <div class="dashboard-card">
                <h3>Inscriptions Acceptées</h3>
                <div class="big-number"><?= $total_inscriptions ?></div>
                <p>Total des inscriptions</p>
            </div>

            <div class="dashboard-card">
                <h3>Inscriptions en Attente</h3>
                <div class="big-number"><?= $pending_inscriptions ?></div>
                <p>Total des inscriptions en attente</p>
            </div>

            <div class="dashboard-card">
                <h3>Cours Créés</h3>
                <div class="big-number"><?= $total_courses ?></div>
                <p>Total des cours</p>
            </div>
        </div>

    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Confirmation de déconnexion
            const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
            logoutLinks.forEach(link => {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    showConfirmAlert('Voulez-vous vraiment vous déconnecter ?', () => {
                        window.location.href = this.href;
                    }, 'Déconnexion');
                });
            });

            // Confirmation de suppression de cours ou de module
            const deleteLinks = document.querySelectorAll('.delete-action');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    showConfirmAlert('Voulez-vous vraiment supprimer cet élément ?', () => {
                        window.location.href = this.href;
                    }, 'Suppression');
                });
            });
        });
    </script>
</body>

</html>