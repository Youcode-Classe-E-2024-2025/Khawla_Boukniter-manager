<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';

checkAccess([1, 2]); 

$user = $_SESSION['user'];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$course_filter = isset($_GET['course']) ? (int)$_GET['course'] : null;
$status_filter = isset($_GET['status']) ? (int)$_GET['status'] : null;
$modules_per_page = 6;
$offset = ($page - 1) * $modules_per_page;

try {

    $courses_stmt = $pdo->prepare("
        SELECT id, titre 
        FROM cours 
        WHERE formateur_id = ? AND is_active = true
        ORDER BY titre
    ");
    $courses_stmt->execute([$user['id']]);
    $user_courses = $courses_stmt->fetchAll();

    $query = "
        SELECT m.id, m.titre, m.description, m.ordre, 
               m.date_creation,
               c.titre as cours_titre,
               c.id as course_id,
               (SELECT COUNT(DISTINCT user_id) 
                FROM inscriptions 
                WHERE course_id = c.id) as inscriptions_count,
               m.is_archived
        FROM modules m
        JOIN cours c ON m.course_id = c.id
        WHERE c.formateur_id = :user_id 
              AND c.is_active = true 
    ";

    $params = [':user_id' => $user['id']];

    if ($course_filter) {
        $query .= " AND c.id = :course_id";
        $params[':course_id'] = $course_filter;
    }

    $query .= "
        GROUP BY m.id, m.date_creation, c.id, c.titre, m.is_archived
        ORDER BY m.date_creation DESC
        LIMIT :limit OFFSET :offset
    ";
    $params[':limit'] = $modules_per_page;
    $params[':offset'] = $offset;

    $modules_stmt = $pdo->prepare($query);

    foreach ($params as $key => &$val) {
        $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $modules_stmt->bindParam($key, $val, $type);
    }

    $modules_stmt->execute();
    $modules = $modules_stmt->fetchAll();

    $count_query = "
        SELECT COUNT(m.id) as total 
        FROM modules m
        JOIN cours c ON m.course_id = c.id
        WHERE c.formateur_id = :user_id 
              AND c.is_active = true 
    ";

    $count_params = [':user_id' => $user['id']];

    if ($course_filter) {
        $count_query .= " AND c.id = :course_id";
        $count_params[':course_id'] = $course_filter;
    }

    $count_stmt = $pdo->prepare($count_query);

    foreach ($count_params as $key => &$val) {
        $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $count_stmt->bindParam($key, $val, $type);
    }

    $count_stmt->execute();
    $total_modules = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_modules / $modules_per_page);

} catch (PDOException $e) {
    logError("Erreur de récupération des modules : " . $e->getMessage());
    $modules = [];
    $total_pages = 0;
    $total_modules = 0;
    echo "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Modules - Formateur</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .total-modules {
            text-align: center;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .module-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .module-card:hover {
            transform: translateY(-5px);
        }

        .module-card-stats {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .module-card-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .course-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .course-filter select {
            padding: 5px;
            border-radius: 5px;
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
            <h1>Mes Modules</h1>
        </div>

        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Tableau de Bord</a></li>
                <li><a href="create_module.php">Créer un Nouveau Module</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <!-- <div class="filters">
            <div class="course-filter">
                <label for="course-select">Filtrer par cours :</label>
                <select id="course-select" onchange="location.href='?course=' + this.value + '&page=1'">
                    <option value="">Tous les cours</option>
                    <?php foreach ($user_courses as $course): ?>
                        <option value="<?= $course['id'] ?>" 
                                <?= $course_filter == $course['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course['titre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="total-modules">
                <p>Total des modules : <?= $total_modules ?></p>
            </div>
        </div> -->

        <div class="modules-grid">
            <?php if (empty($modules)): ?>
                <div class="alert alert-info">
                    <p>Aucun module trouvé. 
                        <?= $course_filter ? 'Aucun module pour ce cours.' : 'Commencez par <a href="create_module.php">créer votre premier module</a>.' ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($modules as $module): ?>
                    <div class="module-card">
                        <h3><?= htmlspecialchars($module['titre']) ?></h3>
                        <p><?= htmlspecialchars(substr($module['description'], 0, 100)) . (strlen($module['description']) > 100 ? '...' : '') ?></p>

                        <div class="module-card-stats">
                            <span>📚 <?= htmlspecialchars($module['cours_titre']) ?> Cours</span>
                        </div>

                        <div class="module-card-actions">
                            <small>📅 Créé le <?= date('d/m/Y', strtotime($module['date_creation'])) ?></small>
                            <div>
                                <?php if (!$module['is_archived']): ?>
                                    <a href="edit_module.php?id=<?= $module['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                                    <a href="#" class="btn btn-sm btn-primary">Détails</a>
                                <?php else: ?>
                                    <a href="activate_module.php?id=<?= $module['id'] ?>&csrf_token=<?= urlencode($csrf_token) ?>" 
                                       class="btn btn-sm btn-success" 
                                       onclick="return confirm('Voulez-vous vraiment activer ce module ?')">
                                        <i class="fas fa-check-circle"></i> Activer
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div >
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php 
                    $page_url = "?page=$i";
                    if ($course_filter) {
                        $page_url .= "&course=" . $course_filter;
                    }
                    ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= $page_url ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>