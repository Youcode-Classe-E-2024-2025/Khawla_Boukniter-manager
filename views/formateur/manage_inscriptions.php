<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['inscription_id'])) {
        $action = $_POST['action'];
        $inscription_id = $_POST['inscription_id'];

        try {
            if ($action === 'accept') {
                $stmt = $pdo->prepare("
                    UPDATE inscriptions i
                    JOIN cours c ON i.course_id = c.id
                    SET i.status = 'accepte'
                    WHERE i.id = ? AND c.formateur_id = ?
                ");
                $stmt->execute([$inscription_id, $_SESSION['user']['id']]);
                $message = "Inscription acceptée avec succès.";
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("
                    UPDATE inscriptions i
                    JOIN cours c ON i.course_id = c.id
                    SET i.status = 'refuse'
                    WHERE i.id = ? AND c.formateur_id = ?
                ");
                $stmt->execute([$inscription_id, $_SESSION['user']['id']]);
                $message = "Inscription rejetée.";
            }
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            i.id, 
            i.user_id, 
            u.nom, 
            u.prenom, 
            c.titre AS course_titre 
        FROM inscriptions i
        JOIN users u ON i.user_id = u.id
        JOIN cours c ON i.course_id = c.id
        WHERE c.formateur_id = ? AND i.status = 'en_attente'
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    $pending_inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur de récupération des inscriptions : " . $e->getMessage();
    $pending_inscriptions = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Inscriptions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .inscriptions-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
        }
        .inscriptions-table thead {
            background-color: #3498db;
            color: white;
        }
        .inscriptions-table th, .inscriptions-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .inscriptions-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .inscriptions-table tr:hover {
            background-color: #e6f2ff;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            margin: 5px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .no-inscriptions {
            text-align: center;
            color: #6c757d;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .nav {
            margin-bottom: 20px;
            text-align: center;
        }
        .nav a {
            color: #3498db;
            text-decoration: none;
            margin: 0 10px;
            font-weight: bold;
        }
        .nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-graduate"></i> Gérer les Inscriptions</h1>
            <p>Examinez et gérez les demandes d'inscription en attente</p>
        </div>

        <div class="nav">
            <a href="../formateur/dashboard.php"><i class="fas fa-home"></i> Tableau de bord</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($pending_inscriptions)): ?>
            <div class="no-inscriptions">
                <i class="fas fa-inbox"></i> Aucune inscription en attente pour le moment.
            </div>
        <?php else: ?>
            <table class="inscriptions-table">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Cours</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_inscriptions as $inscription): ?>
                    <tr>
                        <td>
                            <i class="fas fa-user"></i> 
                            <?= htmlspecialchars($inscription['prenom'] . ' ' . $inscription['nom']) ?>
                        </td>
                        <td>
                            <i class="fas fa-book"></i> 
                            <?= htmlspecialchars($inscription['course_titre']) ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="inscription_id" value="<?= $inscription['id'] ?>">
                                <button type="submit" name="action" value="accept" class="btn btn-success">
                                    <i class="fas fa-check"></i> Accepter
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger">
                                    <i class="fas fa-times"></i> Rejeter
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <footer class="footer" style="text-align: center; margin-top: 20px; color: #6c757d;">
        <p>&copy; 2024 Système de Gestion de Formations. Tous droits réservés.</p>
    </footer>
</body>
</html>
