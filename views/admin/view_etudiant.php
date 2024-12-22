<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1]);

$etudiant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($etudiant_id <= 0) {
    $_SESSION['error'] = "ID d'étudiant invalide.";
    header('Location: etudiants.php');
    exit();
}

try {

    $stmt = $pdo->prepare("
        SELECT u.id, u.nom, u.prenom, u.email, 
               u.is_active, u.date_creation,
               COUNT(DISTINCT i.course_id) as cours_inscrits,
               COUNT(DISTINCT p.id) as progres_total
        FROM users u
        LEFT JOIN inscriptions i ON u.id = i.user_id
        LEFT JOIN progression p ON u.id = p.user_id
        WHERE u.id = ? AND u.role_id = 3
        GROUP BY u.id
    ");
    $stmt->execute([$etudiant_id]);
    $etudiant = $stmt->fetch();

    if (!$etudiant) {
        $_SESSION['error'] = "Étudiant non trouvé.";
        header('Location: etudiants.php');
        exit();
    }

    $cours_stmt = $pdo->prepare("
        SELECT c.id, c.titre, c.niveau, 
               c.date_creation, 
               (SELECT COUNT(*) FROM modules m WHERE m.course_id = c.id) as modules_count,
               p.progression_totale
        FROM inscriptions i
        JOIN cours c ON i.course_id = c.id
        LEFT JOIN (
            SELECT course_id, 
                   SUM(progression) / COUNT(*) as progression_totale
            FROM progression
            WHERE user_id = ?
            GROUP BY course_id
        ) p ON p.course_id = c.id
        WHERE i.user_id = ?
        ORDER BY c.date_creation DESC
    ");
    $cours_stmt->execute([$etudiant_id, $etudiant_id]);
    $cours_inscrits = $cours_stmt->fetchAll();

} catch (PDOException $e) {
    logError("Erreur de récupération des détails de l'étudiant : " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la récupération des détails.";
    header('Location: etudiants.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'Étudiant</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <!-- <link rel="stylesheet" href="https: -->
    <style>
        .etudiant-details {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
        }
        .cours-inscrits {
            margin-top: 20px;
        }
        .cours-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cours-table th, 
        .cours-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .cours-table th {
            background-color: var(--primary-color);
            color: white;
        }
        .progress-bar {
            width: 100%;
            background-color: #e0e0e0;
            padding: 3px;
            border-radius: 3px;
        }
        .progress-bar-fill {
            height: 20px;
            background-color: #4CAF50;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Détails de l'Étudiant</h1>
        </div>

        <div class="nav">
            <ul>
                <li><a href="etudiants.php">Liste des Étudiants</a></li>
                <li><a href="../admin/dashboard.php">Tableau de Bord</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <div class="content">
            <div class="etudiant-details">
                <h2>Informations Personnelles</h2>
                <p><strong>Nom :</strong> <?= htmlspecialchars($etudiant['nom']) ?></p>
                <p><strong>Prénom :</strong> <?= htmlspecialchars($etudiant['prenom']) ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($etudiant['email']) ?></p>
                <p><strong>Date de Création :</strong> <?= date('d/m/Y H:i', strtotime($etudiant['date_creation'])) ?></p>
                <p>
                    <strong>Statut :</strong> 
                    <?= $etudiant['is_active'] ? 
                        '<span class="badge badge-success">Actif</span>' : 
                        '<span class="badge badge-danger">Inactif</span>' 
                    ?>
                </p>
            </div>

            <div class="cours-inscrits">
                <h2>Cours Inscrits (<?= count($cours_inscrits) ?>)</h2>
                <?php if (!empty($cours_inscrits)): ?>
                    <table class="cours-table">
                        <thead>
                            <tr>
                                <th>Titre du Cours</th>
                                <th>Niveau</th>
                                <th>Date d'Inscription</th>
                                <th>Modules</th>
                                <th>Progression</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cours_inscrits as $cours): ?>
                                <tr>
                                    <td><?= htmlspecialchars($cours['titre']) ?></td>
                                    <td><?= htmlspecialchars($cours['niveau']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($cours['date_creation'])) ?></td>
                                    <td><?= $cours['modules_count'] ?> modules</td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-bar-fill" 
                                                 style="width: <?= $cours['progression_totale'] ?? 0 ?>%">
                                                <?= number_format($cours['progression_totale'] ?? 0, 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucun cours inscrit.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>
