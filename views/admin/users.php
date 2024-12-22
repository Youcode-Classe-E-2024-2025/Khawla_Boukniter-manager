<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';

if (!function_exists('logError')) {
    function logError($message) {
        error_log($message, 3, __DIR__ . '/../../logs/error.log');
    }
}

checkAccess([1]); 

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$users_per_page = 10;
$offset = ($page - 1) * $users_per_page;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : null;
$role_filter = isset($_GET['role']) ? (int)$_GET['role'] : null;

try {
    $roles_stmt = $pdo->query("SELECT id, nom FROM roles ORDER BY id");
    $roles = $roles_stmt->fetchAll(PDO::FETCH_ASSOC);

    $query = "
        SELECT 
            u.id, 
            u.nom, 
            u.prenom, 
            u.email, 
            u.is_active as status, 
            u.is_banned,
            u.date_creation,
            r.nom as role_nom
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE 1=1
    ";

    $params = [];

    if ($search_query) {
        $query .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $search_param = "%{$search_query}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if ($role_filter) {
        $query .= " AND u.role_id = ?";
        $params[] = $role_filter;
    }

    $query .= " ORDER BY u.date_creation DESC LIMIT ? OFFSET ?";
    $params[] = $users_per_page;
    $params[] = $offset;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count_query = "
        SELECT COUNT(*) as total 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE 1=1
    ";

    $count_params = [];

    if ($search_query) {
        $count_query .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $search_param = "%{$search_query}%";
        $count_params[] = $search_param;
        $count_params[] = $search_param;
        $count_params[] = $search_param;
    }

    if ($role_filter) {
        $count_query .= " AND u.role_id = ?";
        $count_params[] = $role_filter;
    }

    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($count_params);
    $total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_users / $users_per_page);

} catch (PDOException $e) {
    error_log("Erreur de récupération des utilisateurs : " . $e->getMessage());
    error_log("Trace de l'erreur : " . $e->getTraceAsString());
    
    $users = [];
    $total_pages = 0;
    $total_users = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th, 
        .users-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .users-table th {
            background-color: var(--primary-color);
            color: white;
        }

        .users-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .users-table tr:hover {
            background-color: #e6e6e6;
        }

        .status-active {
            color: green;
            font-weight: bold;
        }

        .status-inactive {
            color: red;
            font-weight: bold;
        }

        .btn-action {
            display: inline-block;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-right: 5px;
        }

        .btn-edit {
            background-color: #28a745;
            color: white;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-toggle {
            background-color: #ffc107;
            color: black;
        }

        .btn-ban {
            background-color: #ed6133;
            color: white;
        }

        .btn-unban {
            background-color: #28a745;
            color: white;
        }

        .search-filter {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a, 
        .pagination span {
            margin: 0 5px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: var(--primary-color);
        }

        .pagination .current {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestion des Utilisateurs</h1>
        </div>

        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Tableau de Bord</a></li>
                <li><a href="comprehensive_management.php">Gestion Globale</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <div class="search-filter">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Rechercher un utilisateur" 
                       value="<?= htmlspecialchars($search_query ?? '') ?>">
                
                <select name="role">
                    <option value="">Tous les rôles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" 
                                <?= $role_filter == $role['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($role['nom'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary">Filtrer</button>
            </form>
            
            <a href="user_management.php?action=create_user" class="btn btn-success">
                Ajouter un Utilisateur
            </a>
        </div>

        <?php if (empty($users)): ?>
            <div class="alert alert-info">
                <p>Aucun utilisateur trouvé.</p>
            </div>
        <?php else: ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Banni</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['nom']) ?></td>
                            <td><?= htmlspecialchars($user['prenom']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($user['role_nom'])) ?></td>
                            <td>
                                <?php if ($user['status'] == 1): ?>
                                    <span class="status-active">Actif</span>
                                <?php else: ?>
                                    <span class="status-inactive">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-<?= $user['is_banned'] == 1 ? 'active' : 'inactive' ?>">
                                    <?= $user['is_banned'] == 1 ? 'Oui' : 'Non' ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn-action btn-edit">Modifier</a>
                                
                                <?php if ($user['is_banned'] == 0): ?>
                                    <a href="ban_user.php?user_id=<?= $user['id'] ?>&action=ban" class="btn-action btn-ban" onclick="return confirm('Voulez-vous vraiment bannir cet utilisateur ?')">Bannir</a>
                                <?php else: ?>
                                    <a href="ban_user.php?user_id=<?= $user['id'] ?>&action=unban" class="btn-action btn-unban" onclick="return confirm('Voulez-vous vraiment débannir cet utilisateur ?')">Débannir</a>
                                <?php endif; ?>
                                
                                <a href="#" class="btn-action btn-delete" onclick="return confirmDelete(<?= $user['id'] ?>)">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search_query) ?>&role=<?= $role_filter ?>">Précédent</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search_query) ?>&role=<?= $role_filter ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search_query) ?>&role=<?= $role_filter ?>">Suivant</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function confirmDelete(userId) {
        if (confirm('Voulez-vous vraiment supprimer cet utilisateur ? Cette action est irréversible.')) {
            window.location.href = 'delete_user.php?user_id=' + userId;
            return false;
        }
        return false;
    }
    </script>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>
