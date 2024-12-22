<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([2]); 

$user = $_SESSION['user'];

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$titre = '';
$description = '';
$niveau = '';
$is_active = true;
$erreurs = [];

try {
    $cours_stmt = $pdo->prepare("
        SELECT id, titre, description, niveau, is_active
        FROM cours 
        WHERE id = ? AND formateur_id = ?
    ");
    $cours_stmt->execute([$course_id, $user['id']]);
    $cours = $cours_stmt->fetch();

    if (!$cours) {
        $_SESSION['message_erreur'] = "Vous n'avez pas le droit de modifier ce cours.";
        header("Location: courses.php");
        exit();
    }

    $titre = $cours['titre'];
    $description = $cours['description'];
    $niveau = $cours['niveau'];
    $is_active = $cours['is_active'];

} catch (PDOException $e) {
    logError("Erreur de récupération du cours : " . $e->getMessage());
    $_SESSION['message_erreur'] = "Une erreur est survenue lors de la récupération du cours.";
    header("Location: courses.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!CSRFToken::verifyToken($_POST['csrf_token'])) {
        die("Erreur de sécurité : Token CSRF invalide");
    }

    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $niveau = trim($_POST['niveau'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($titre)) {
        $erreurs[] = "Le titre du cours est obligatoire.";
    }

    if (empty($description)) {
        $erreurs[] = "La description du cours est obligatoire.";
    }

    if (empty($niveau)) {
        $erreurs[] = "Le niveau du cours est obligatoire.";
    }

    if (empty($erreurs)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE cours 
                SET titre = ?, description = ?, niveau = ?, is_active = ?
                WHERE id = ? AND formateur_id = ?
            ");
            $stmt->execute([
                $titre, 
                $description, 
                $niveau,
                $is_active,
                $course_id,
                $user['id']
            ]);

            $_SESSION['message_succes'] = "Le cours a été mis à jour avec succès !";
            header("Location: courses.php");
            exit();

        } catch (PDOException $e) {
            logError("Erreur de mise à jour du cours : " . $e->getMessage());
            $erreurs[] = "Une erreur est survenue lors de la mise à jour du cours.";
        }
    }
}

$csrf_token = CSRFToken::generateToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Cours - Formateur</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https:
    <style>
        .form-course {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-course h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input, 
        .form-group textarea, 
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-group .checkbox-container {
            display: flex;
            align-items: center;
        }

        .form-group .checkbox-container input {
            width: auto;
            margin-right: 10px;
        }

        .form-errors {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Modifier un Cours</h1>
        </div>

        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Tableau de Bord</a></li>
                <li><a href="courses.php">Mes Cours</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <div class="form-course">
            <?php if (!empty($erreurs)): ?>
                <div class="form-errors">
                    <ul>
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= htmlspecialchars($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="form-group">
                    <label for="titre">Titre du Cours</label>
                    <input type="text" id="titre" name="titre" 
                           value="<?= htmlspecialchars($titre) ?>" 
                           required placeholder="Ex: Introduction à la Programmation">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              rows="5" required 
                              placeholder="Décrivez brièvement le contenu et les objectifs du cours"><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="niveau">Niveau</label>
                    <select id="niveau" name="niveau" required>
                        <option value="">Sélectionnez le niveau</option>
                        <option value="debutant" <?= $niveau === 'debutant' ? 'selected' : '' ?>>Débutant</option>
                        <option value="intermediaire" <?= $niveau === 'intermediaire' ? 'selected' : '' ?>>Intermédiaire</option>
                        <option value="avance" <?= $niveau === 'avance' ? 'selected' : '' ?>>Avancé</option>
                    </select>
                </div>

                <div class="form-group">
                    <div class="checkbox-container">
                        <input type="checkbox" id="is_active" name="is_active" 
                               <?= $is_active ? 'checked' : '' ?>>
                        <label for="is_active">Cours actif</label>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Mettre à jour le Cours</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>
