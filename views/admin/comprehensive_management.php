<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';

checkAccess(['1']);

function getCounts($pdo) {
    $counts = [
        'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'formateurs' => $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 2")->fetchColumn(),
        'etudiants' => $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn(),
        'cours' => $pdo->query("SELECT COUNT(*) FROM cours")->fetchColumn(),
        'modules' => $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn()
    ];
    return $counts;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
    $stmt->execute([$new_role, $user_id]);
    $success_message = "Rôle de l'utilisateur mis à jour avec succès.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_course_status'])) {
    $course_id = $_POST['course_id'];
    
    $stmt = $pdo->prepare("SELECT is_active FROM cours WHERE id = ?");
    $stmt->execute([$course_id]);
    $current_status = $stmt->fetchColumn();
    
    $new_status = $current_status ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE cours SET is_active = ? WHERE id = ?");
    $stmt->execute([$new_status, $course_id]);
    $success_message = "Statut du cours mis à jour avec succès.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_module_status'])) {
    $module_id = $_POST['module_id'];
    
    $stmt = $pdo->prepare("SELECT is_archived FROM modules WHERE id = ?");
    $stmt->execute([$module_id]);
    $current_status = $stmt->fetchColumn();
    
    $new_status = $current_status ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE modules SET is_archived = ? WHERE id = ?");
    $stmt->execute([$new_status, $module_id]);
    $success_message = "Statut du module mis à jour avec succès.";
}

$stmt = $pdo->query("
    SELECT u.id, u.nom, u.prenom, u.email, r.nom AS role 
    FROM users u
    JOIN roles r ON u.role_id = r.id
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$course_stmt = $pdo->query("
    SELECT c.id, c.titre, c.description, c.is_active, u.nom AS formateur_nom 
    FROM cours c
    JOIN users u ON c.formateur_id = u.id
");
$courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

$module_stmt = $pdo->query("
    SELECT m.id, m.titre, m.description, m.is_archived, c.titre AS course_titre 
    FROM modules m
    JOIN cours c ON m.course_id = c.id
");
$modules = $module_stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = getCounts($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Complète - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            background-color: var(--background-color);
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 300;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .management-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .management-card {
            background-color: var(--card-background);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 25px;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .management-card:hover {
            transform: translateY(-5px);
        }

        .management-card h2 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .table-container {
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            border: 1px solid #e0e0e0;
            padding: 10px;
            text-align: left;
            white-space: nowrap;
        }

        table th {
            background-color: var(--primary-color);
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        form select, form button {
            padding: 8px;
            margin: 5px 0;
            border-radius: 4px;
        }

        form button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        form button:hover {
            background-color: #2980b9;
        }

        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }

        form button.btn-activate {
            background-color: #28a745;
        }

        form button.btn-deactivate {
            background-color: #dc3545;
        }

        @media (max-width: 768px) {
            .management-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .dashboard-card {
            background-color: var(--card-background);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 25px;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .card-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .card-content {
            text-align: center;
        }
        
        .big-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <ul>
            <li><a href="../admin/users.php">Utilisateurs</a></li>
            <li><a href="../../auth/logout.php" class="logout">Déconnexion</a></li>
        </ul>
    </div>

    <div class="container">
        <div class="header">
            <h1>Gestion Complète</h1>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <h3>Utilisateurs Total</h3>
                    <p class="big-number"><?= $counts['users'] ?></p>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="card-content">
                    <h3>Formateurs</h3>
                    <p class="big-number"><?= $counts['formateurs'] ?></p>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="card-content">
                    <h3>Étudiants</h3>
                    <p class="big-number"><?= $counts['etudiants'] ?></p>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="card-content">
                    <h3>Cours</h3>
                    <p class="big-number"><?= $counts['cours'] ?></p>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="card-content">
                    <h3>Modules</h3>
                    <p class="big-number"><?= $counts['modules'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="management-grid">
            <div class="management-card">
                <h2>Gestion des Utilisateurs</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle Actuel</th>
                                <th>Changer Rôle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['role']) ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <select name="new_role">
                                            <?php 
                                            $stmt = $pdo->query("SELECT * FROM roles");
                                            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($roles as $role): ?>
                                            <option value="<?= $role['id'] ?>"><?= $role['nom'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="change_role">Changer</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="management-card">
                <h2>Gestion des Cours</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Description</th>
                                <th>Formateur</th>
                                <th>Statut</th>
                                <th>Changer Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?= htmlspecialchars($course['titre']) ?></td>
                                <td><?= htmlspecialchars($course['description']) ?></td>
                                <td><?= htmlspecialchars($course['formateur_nom']) ?></td>
                                <td class="<?= $course['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $course['is_active'] ? 'Actif' : 'Inactif' ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                        <button type="submit" name="toggle_course_status" class="<?= $course['is_active'] ? 'btn-deactivate' : 'btn-activate' ?>"><?= $course['is_active'] ? 'Désactiver' : 'Activer' ?></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="management-card">
                <h2>Gestion des Modules</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Description</th>
                                <th>Cours</th>
                                <th>Statut</th>
                                <th>Changer Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $module): ?>
                            <tr>
                                <td><?= htmlspecialchars($module['titre']) ?></td>
                                <td><?= htmlspecialchars($module['description']) ?></td>
                                <td><?= htmlspecialchars($module['course_titre']) ?></td>
                                <td class="<?= $module['is_archived'] ? 'status-inactive' : 'status-active' ?>"><?= $module['is_archived'] ? 'Archivé' : 'Actif' ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                        <button type="submit" name="toggle_module_status" class="<?= $module['is_archived'] ? 'btn-activate' : 'btn-deactivate' ?>"><?= $module['is_archived'] ? 'Activer' : 'Archiver' ?></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
