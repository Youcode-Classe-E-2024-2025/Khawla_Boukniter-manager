<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';

checkAccess(['1']);

// Function to get counts
function getCounts($pdo) {
    $counts = [
        'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'formateurs' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'formateur'")->fetchColumn(),
        'etudiants' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'etudiant'")->fetchColumn(),
        'courses' => $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
        'modules' => $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn()
    ];
    return $counts;
}

// Handle Role Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$new_role, $user_id]);
    $success_message = "Rôle de l'utilisateur mis à jour avec succès.";
}

// Fetch Users
$stmt = $pdo->query("SELECT id, nom, prenom, email, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Courses
$course_stmt = $pdo->query("
    SELECT c.id, c.titre, c.description, u.nom AS formateur_nom 
    FROM courses c
    JOIN users u ON c.formateur_id = u.id
");
$courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Modules
$module_stmt = $pdo->query("
    SELECT m.id, m.titre, m.description, c.titre AS course_titre 
    FROM modules m
    JOIN courses c ON m.course_id = c.id
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
    <style>
        .management-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .management-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tableau de Gestion Administratif</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <div class="management-grid">
            <div class="management-card">
                <h2>Statistiques</h2>
                <p>Utilisateurs Total: <?= $counts['users'] ?></p>
                <p>Formateurs: <?= $counts['formateurs'] ?></p>
                <p>Étudiants: <?= $counts['etudiants'] ?></p>
                <p>Cours: <?= $counts['courses'] ?></p>
                <p>Modules: <?= $counts['modules'] ?></p>
            </div>
            
            <div class="management-card">
                <h2>Gestion des Utilisateurs</h2>
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
                                        <option value="etudiant">Étudiant</option>
                                        <option value="formateur">Formateur</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                    <button type="submit" name="change_role">Changer</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="management-card">
                <h2>Gestion des Cours</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Description</th>
                            <th>Formateur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?= htmlspecialchars($course['titre']) ?></td>
                            <td><?= htmlspecialchars($course['description']) ?></td>
                            <td><?= htmlspecialchars($course['formateur_nom']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="management-card">
                <h2>Gestion des Modules</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Description</th>
                            <th>Cours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                        <tr>
                            <td><?= htmlspecialchars($module['titre']) ?></td>
                            <td><?= htmlspecialchars($module['description']) ?></td>
                            <td><?= htmlspecialchars($module['course_titre']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
