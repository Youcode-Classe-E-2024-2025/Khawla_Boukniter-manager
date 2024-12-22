<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1]);

$csrf_token = CSRFToken::generateToken();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$etudiants_par_page = 10;
$offset = ($page - 1) * $etudiants_par_page;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : null;

try {

    $query = "
        SELECT u.id, u.nom, u.prenom, u.email, 
               COUNT(DISTINCT i.course_id) as cours_inscrits,
               u.is_active,
               u.date_creation
        FROM users u
        LEFT JOIN inscriptions i ON u.id = i.user_id
        WHERE u.role_id = 3 
    ";

    if ($search_query) {
        $query .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
    }

    $query .= "
        GROUP BY u.id
        ORDER BY u.date_creation DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $pdo->prepare($query);

    $param_index = 1;
    if ($search_query) {
        $search_param = "%{$search_query}%";
        $stmt->bindValue($param_index++, $search_param);
        $stmt->bindValue($param_index++, $search_param);
        $stmt->bindValue($param_index++, $search_param);
    }
    $stmt->bindValue($param_index++, $etudiants_par_page, PDO::PARAM_INT);
    $stmt->bindValue($param_index, $offset, PDO::PARAM_INT);

    $stmt->execute();
    $etudiants = $stmt->fetchAll();

    $count_query = "
        SELECT COUNT(*) as total 
        FROM users 
        WHERE role_id = 3
    ";
    if ($search_query) {
        $count_query .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    }
    $count_stmt = $pdo->prepare($count_query);

    $param_index = 1;
    if ($search_query) {
        $search_param = "%{$search_query}%";
        $count_stmt->bindValue($param_index++, $search_param);
        $count_stmt->bindValue($param_index++, $search_param);
        $count_stmt->bindValue($param_index, $search_param);
    }
    $count_stmt->execute();
    $total_etudiants = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_etudiants / $etudiants_par_page);

} catch (PDOException $e) {
    logError("Erreur de récupération des étudiants : " . $e->getMessage());
    $etudiants = [];
    $total_pages = 0;
    $total_etudiants = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https:
    <style>
        .etudiants-table {
            width: 100%;
            border-collapse: collapse;
        }
        .etudiants-table th, 
        .etudiants-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .etudiants-table th {
            background-color: var(--primary-color);
            color: white;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .btn-sm {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
        }
        .btn-view {
            background-color: #3498db;
            color: white;
        }
        .btn-edit {
            background-color: #2ecc71;
            color: white;
        }
        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }
        .btn-activate {
            background-color: #27ae60;
            color: white;
        }
        .btn-deactivate {
            background-color: #f39c12;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestion des Étudiants</h1>
        </div>

        <div class="nav">
            <ul>
                <li><a href="../admin/dashboard.php">Tableau de Bord</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <div class="content">
            <div class="search-section">
                <form method="GET" action="">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Rechercher un étudiant" 
                               value="<?= htmlspecialchars($search_query ?? '') ?>">
                        <button type="submit" class="btn">Rechercher</button>
                    </div>
                </form>
            </div>

            <div class="stats">
                <p>Total des étudiants : <?= $total_etudiants ?></p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error'] ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($etudiants)): ?>
                <table class="etudiants-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Cours Inscrits</th>
                            <th>Date Création</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($etudiants as $etudiant): ?>
                            <tr>
                                <td><?= htmlspecialchars($etudiant['nom']) ?></td>
                                <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
                                <td><?= htmlspecialchars($etudiant['email']) ?></td>
                                <td><?= $etudiant['cours_inscrits'] ?></td>
                                <td><?= date('d/m/Y', strtotime($etudiant['date_creation'])) ?></td>
                                <td>
                                    <?= $etudiant['is_active'] ? 
                                        '<span class="badge badge-success">Actif</span>' : 
                                        '<span class="badge badge-danger">Inactif</span>' 
                                    ?>
                                </td>
                                <td class="actions">
                                    <a href="view_etudiant.php?id=<?= $etudiant['id'] ?>" 
                                       class="btn btn-sm btn-view">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                    <?php if ($etudiant['is_active']): ?>
                                        <a href="deactivate_etudiant.php?id=<?= $etudiant['id'] ?>&csrf_token=<?= urlencode($csrf_token) ?>" 
                                           class="btn btn-sm btn-deactivate"
                                           onclick="return confirm('Voulez-vous vraiment désactiver cet étudiant ?')">
                                            <i class="fas fa-ban"></i> Désactiver
                                        </a>
                                    <?php else: ?>
                                        <a href="activate_etudiant.php?id=<?= $etudiant['id'] ?>&csrf_token=<?= urlencode($csrf_token) ?>" 
                                           class="btn btn-sm btn-activate"
                                           onclick="return confirm('Voulez-vous vraiment activer cet étudiant ?')">
                                            <i class="fas fa-check-circle"></i> Activer
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>">Précédent</a>
                    <?php endif; ?>

                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);

                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?= $i ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>" 
                           class="<?= $i == $page ? 'current' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>">Suivant</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>Aucun étudiant trouvé.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>
