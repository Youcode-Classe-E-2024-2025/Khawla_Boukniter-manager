<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1]);

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    $_SESSION['error'] = "ID d'utilisateur invalide.";
    header('Location: users.php');
    exit();
}

try {

    $stmt = $pdo->prepare("
        SELECT u.id, u.nom, u.prenom, u.email, 
               u.is_active, u.date_creation, 
               r.nom as role_nom,
               u.role_id
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header('Location: users.php');
        exit();
    }

    if ($user['role_id'] == 2) {  
        $details_stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT c.id) as cours_crees,
                COUNT(DISTINCT m.id) as modules_crees,
                COUNT(DISTINCT i.user_id) as total_etudiants
            FROM users u
            LEFT JOIN cours c ON u.id = c.formateur_id
            LEFT JOIN modules m ON c.id = m.course_id
            LEFT JOIN inscriptions i ON c.id = i.course_id
            WHERE u.id = ?
        ");
    } elseif ($user['role_id'] == 3) {  
        $details_stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT i.course_id) as cours_inscrits,
                COUNT(DISTINCT p.id) as progres_total,
                SUM(p.progression) / COUNT(DISTINCT p.id) as progression_moyenne
            FROM users u
            LEFT JOIN inscriptions i ON u.id = i.user_id
            LEFT JOIN progression p ON u.id = p.user_id
            WHERE u.id = ?
        ");
    }

    $details_stmt->execute([$user_id]);
    $details = $details_stmt->fetch();

} catch (PDOException $e) {
    logError("Erreur de récupération des détails de l'utilisateur : " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la récupération des détails.";
    header('Location: users.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'Utilisateur</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https:
    <style>
        .user-details {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
        }
        .user-stats {
            margin-top: 20px;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }
        .stats-table th, 
        .stats-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .stats-table th {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Détails de l'Utilisateur</h1>
        </div>

        <div class="nav">
            <ul>
                <li><a href="users.php">Liste des Utilisateurs</a></li>
                <li><a href="../admin/dashboard.php">Tableau de Bord</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <div class="content">
            <div class="user-details">
                <h2>Informations Personnelles</h2>
                <p><strong>Nom :</strong> <?= htmlspecialchars($user['nom']) ?></p>
                <p><strong>Prénom :</strong> <?= htmlspecialchars($user['prenom']) ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Rôle :</strong> <?= htmlspecialchars($user['role_nom']) ?></p>
                <p><strong>Date de Création :</strong> <?= date('d/m/Y H:i', strtotime($user['date_creation'])) ?></p>
                <p>
                    <strong>Statut :</strong> 
                    <?= $user['is_active'] ? 
                        '<span class="badge badge-success">Actif</span>' : 
                        '<span class="badge badge-danger">Inactif</span>' 
                    ?>
                </p>
            </div>

            <div class="user-stats">
                <h2>Statistiques</h2>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Catégorie</th>
                            <th>Valeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($user['role_id'] == 2): ?>
                            <tr>
                                <td>Cours Créés</td>
                                <td><?= $details['cours_crees'] ?></td>
                            </tr>
                            <tr>
                                <td>Modules Créés</td>
                                <td><?= $details['modules_crees'] ?></td>
                            </tr>
                            <tr>
                                <td>Total Étudiants</td>
                                <td><?= $details['total_etudiants'] ?></td>
                            </tr>
                        <?php elseif ($user['role_id'] == 3): ?>
                            <tr>
                                <td>Cours Inscrits</td>
                                <td><?= $details['cours_inscrits'] ?></td>
                            </tr>
                            <tr>
                                <td>Progressions Totales</td>
                                <td><?= $details['progres_total'] ?></td>
                            </tr>
                            <tr>
                                <td>Progression Moyenne</td>
                                <td><?= number_format($details['progression_moyenne'] ?? 0, 2) ?>%</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>
