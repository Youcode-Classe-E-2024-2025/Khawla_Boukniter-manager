<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php'; 

checkAccess([1, 2]); 

$user = $_SESSION['user'];

$csrf_token = CSRFToken::generateToken();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$courses_per_page = 10;
$offset = ($page - 1) * $courses_per_page;

$niveau_filter = isset($_GET['niveau']) ? trim($_GET['niveau']) : null;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : null;

if ($search_query && $niveau_filter) {
    $search_query = trim($search_query);

    $search_query = preg_replace('/[^a-zA-Z0-9\s]/', '', $search_query); 

    $query = "
        SELECT c.id, c.titre, c.description, c.niveau, 
               c.date_creation, c.is_active,
               COUNT(DISTINCT m.id) as module_count,
               COUNT(DISTINCT i.id) as inscription_count
        FROM cours c
        LEFT JOIN modules m ON c.id = m.course_id
        LEFT JOIN inscriptions i ON c.id = i.course_id
        WHERE c.formateur_id = ? AND c.niveau = ? AND (c.titre LIKE ? OR c.description LIKE ?)
        GROUP BY c.id, c.date_creation
        ORDER BY inscription_count DESC, c.date_creation DESC
        LIMIT ? OFFSET ?
    ";
    $courses_stmt = $pdo->prepare($query);
    $courses_stmt->execute([$user['id'], $niveau_filter, "%{$search_query}%", "%{$search_query}%", $courses_per_page, $offset]);
    $courses = $courses_stmt->fetchAll();
}
try {
    $query = "
        SELECT c.id, c.titre, c.description, c.niveau, 
               c.date_creation, c.is_active,
               COUNT(DISTINCT m.id) as module_count,
               COUNT(DISTINCT i.id) as inscription_count
        FROM cours c
        LEFT JOIN modules m ON c.id = m.course_id
        LEFT JOIN inscriptions i ON c.id = i.course_id
        WHERE c.formateur_id = ? 
        GROUP BY c.id, c.date_creation
        ORDER BY inscription_count DESC, c.date_creation DESC
        LIMIT ? OFFSET ?
    ";
    $courses_stmt = $pdo->prepare($query);
    $courses_stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
    $courses_stmt->bindValue(2, $courses_per_page, PDO::PARAM_INT);
    $courses_stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll();

    $count_query = "
        SELECT COUNT(*) as total 
        FROM cours 
        WHERE formateur_id = ? 
    ";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute([$user['id']]);
    $total_courses = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_courses / $courses_per_page);

} catch (PDOException $e) {
    logError("Erreur de récupération des cours : " . $e->getMessage());
    $courses = [];
    $total_pages = 0;
    $total_courses = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Cours - Formateur</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .courses-filters {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .courses-table {
            width: 100%;
            border-collapse: collapse;
        }

        .courses-table th, 
        .courses-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .courses-table th {
            background-color: var(--primary-color);
            color: white;
        }

        .courses-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .courses-table tr:hover {
            background-color: #e6e6e6;
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

        .btn-create {
            display: inline-block;
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .course-actions {
            display: flex;
            justify-content: space-between;
        }

        .course-actions a {
            margin: 0 5px;
        }

        .btn-edit {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .total-courses {
            justify-self: center;
        }

        .btn {
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Mes Cours</h1>
        </div>

        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Tableau de Bord</a></li>
                <li><a href="create_course.php" class="btn-create">Créer un Nouveau Cours</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <div class="courses-filters">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="niveau">Filtrer par Niveau</label>
                    <select name="niveau" id="niveau">
                        <option value="">Tous les niveaux</option>
                        <option value="debutant" <?= $niveau_filter === 'debutant' ? 'selected' : '' ?>>Débutant</option>
                        <option value="intermediaire" <?= $niveau_filter === 'intermediaire' ? 'selected' : '' ?>>Intermédiaire</option>
                        <option value="avance" <?= $niveau_filter === 'avance' ? 'selected' : '' ?>>Avancé</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="search">Rechercher</label>
                    <input type="text" name="search" id="search" 
                           placeholder="Titre ou description" 
                           value="<?= htmlspecialchars($search_query ?? '') ?>">
                </div>

                <button type="submit" class="btn">Filtrer</button>
            </form>
        </div>

        <div class="total-courses"><p>Total des cours : <?= $total_courses ?></p></div>

        <?php if (empty($courses)): ?>
            <div class="alert alert-info">
                <p>Aucun cours trouvé.</p>
                <p><a href="create_course.php" class="btn">Créer votre premier cours</a></p>
            </div>
        <?php else: ?>
            <table class="courses-table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Description</th>
                        <th>Niveau</th>
                        <th>Modules</th>
                        <th>Inscrits</th>
                        <th>Date de création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?= htmlspecialchars($course['titre']) ?></td>
                            <td><?= htmlspecialchars(substr($course['description'], 0, 100)) . (strlen($course['description']) > 100 ? '...' : '') ?></td>
                            <td><?= htmlspecialchars($course['niveau']) ?></td>
                            <td><?= $course['module_count'] ?> Modules</td>
                            <td><?= $course['inscription_count'] ?> Inscrits</td>
                            <td><?= date('d/m/Y', strtotime($course['date_creation'])) ?></td>
                            <td class="actions">
                                <?php if ($course['is_active']): ?>
                                    <a href="edit_course.php?id=<?= $course['id'] ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <a href="delete_course.php?id=<?= $course['id'] ?>&csrf_token=<?= urlencode($csrf_token) ?>" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('Voulez-vous vraiment désactiver ce cours ?')">
                                        <i class="fas fa-trash"></i> Désactiver
                                    </a>
                                <?php else: ?>
                                    <a href="activate_course.php?id=<?= $course['id'] ?>&csrf_token=<?= urlencode($csrf_token) ?>" 
                                       class="btn btn-success" 
                                       onclick="return confirm('Voulez-vous vraiment activer ce cours ?')">
                                        <i class="fas fa-check-circle"></i> Activer
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php 
                        $url_params = http_build_query([
                            'page' => $i, 
                            'niveau' => $niveau_filter, 
                            'search' => $search_query
                        ]); 
                        ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?<?= $url_params ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>