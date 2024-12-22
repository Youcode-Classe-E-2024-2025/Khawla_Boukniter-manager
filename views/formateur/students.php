<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';

checkAccess([1, 2]);

$user = $_SESSION['user'];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$students_per_page = 10;
$offset = ($page - 1) * $students_per_page;

$module_filter = isset($_GET['module']) ? (int)$_GET['module'] : null;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : null;

try {

    error_log("Current User: " . print_r($user, true));

    $modules_stmt = $pdo->prepare("
        SELECT id, titre 
        FROM cours 
        WHERE formateur_id = ?
        ORDER BY titre
    ");
    $modules_stmt->execute([$user['id']]);
    $modules = $modules_stmt->fetchAll();

    error_log("User Modules: " . print_r($modules, true));

    $query = "
        SELECT DISTINCT 
            u.id, 
            u.nom, 
            u.prenom, 
            u.email, 
            COUNT(DISTINCT i.course_id) as total_courses,
            MAX(i.date_inscription) as derniere_inscription
        FROM users u
        JOIN inscriptions i ON u.id = i.user_id
        JOIN cours c ON i.course_id = c.id
        WHERE c.formateur_id = ?
    ";

    $params = [$user['id']];

    if ($module_filter) {
        $query .= " AND c.id = ?";
        $params[] = $module_filter;
    }

    if ($search_query) {
        $query .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $search_param = "%{$search_query}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $query .= " GROUP BY u.id, u.nom, u.prenom, u.email";
    $query .= " ORDER BY derniere_inscription DESC";
    $query .= " LIMIT ? OFFSET ?";

    $params[] = $students_per_page;
    $params[] = $offset;

    $students_stmt = $pdo->prepare($query);
    $students_stmt->execute($params);
    $students = $students_stmt->fetchAll();

    $count_query = "
        SELECT COUNT(DISTINCT u.id) as total
        FROM users u
        JOIN inscriptions i ON u.id = i.user_id
        JOIN cours c ON i.course_id = c.id
        WHERE c.formateur_id = ?
    ";

    $count_params = [$user['id']];

    if ($module_filter) {
        $count_query .= " AND c.id = ?";
        $count_params[] = $module_filter;
    }

    if ($search_query) {
        $count_query .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $search_param = "%{$search_query}%";
        $count_params[] = $search_param;
        $count_params[] = $search_param;
        $count_params[] = $search_param;
    }

    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($count_params);
    $total_students = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_students / $students_per_page);

} catch (PDOException $e) {
    logError("Erreur de récupération des étudiants : " . $e->getMessage());
    $students = [];
    $total_pages = 0;
    $total_students = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Étudiants - Formateur</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .students-filters {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
        }

        .students-table th, 
        .students-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .students-table th {
            background-color: var(--primary-color);
            color: white;
        }

        .students-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .students-table tr:hover {
            background-color: #e6e6e6;
        }

        .btn-delete {
            display: inline-block;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            background-color: #dc3545;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-delete:hover {
            background-color: #c82333;
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
            <h1>Mes Étudiants</h1>
        </div>

        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Tableau de Bord</a></li>
                <li><a href="modules.php">Mes Modules</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <?php if (empty($students)): ?>
            <div class="alert alert-info">
                <p>Aucun étudiant trouvé.</p>
            </div>
        <?php else: ?>
            <table class="students-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Cours Inscrits</th>
                        <th>Dernière Inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['nom']) ?></td>
                            <td><?= htmlspecialchars($student['prenom']) ?></td>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                            <td><?= $student['total_courses'] ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($student['derniere_inscription']))) ?></td>
                            <td>
                                <a href="#" class="btn-delete" onclick="confirmDeleteStudent(<?= $student['id'] ?>)">
                                    Supprimer
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <script>
            function confirmDeleteStudent(studentId) {
                if (confirm('Voulez-vous vraiment supprimer cet étudiant de tous vos cours ?')) {
                    window.location.href = 'delete_student.php?student_id=' + studentId;
                }
            }
            </script>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&module=<?= $module_filter ?>&search=<?= urlencode($search_query) ?>">Précédent</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&module=<?= $module_filter ?>&search=<?= urlencode($search_query) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&module=<?= $module_filter ?>&search=<?= urlencode($search_query) ?>">Suivant</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>