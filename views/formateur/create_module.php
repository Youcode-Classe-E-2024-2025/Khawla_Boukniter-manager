<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1, 2]); 

$user = $_SESSION['user'];

$titre = '';
$description = '';
$niveau = '';
$erreurs = [];

try {

    $cours = $pdo->query("SELECT id, titre FROM cours WHERE formateur_id = {$user['id']} AND is_active = true")->fetchAll() ;
} catch (PDOException $e) {
    logError("Erreur de récupération des cours : " . $e->getMessage());
    $erreurs[] = "Une erreur est survenue lors de la récupération des cours.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!CSRFToken::verifyToken($_POST['csrf_token'])) {
        die("Erreur de sécurité : Token CSRF invalide");
    }

    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $course_id = trim($_POST['course_id'] ?? '');

    if (empty($titre)) {
        $erreurs[] = "Le titre du module est obligatoire.";
    }

    if (empty($description)) {
        $erreurs[] = "La description du module est obligatoire.";
    }

    if (empty($course_id)) {
        $erreurs[] = "Vous devez sélectionner un cours.";
    }

    if (empty($erreurs)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO modules 
                (course_id, titre, description, ordre) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['course_id'], 
                $titre, 
                $description, 
                0 
            ]);

            $_SESSION['message_succes'] = "Le module a été créé avec succès !";
            header("Location: modules.php");
            exit();

        } catch (PDOException $e) {
            logError("Erreur de création de module : " . $e->getMessage());
            $erreurs[] = "Une erreur est survenue lors de la création du module.";
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
    <title>Créer un Nouveau Module - Formateur</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .form-module {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-module h2 {
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
            <h1>Créer un Nouveau Module</h1>
            <p>Bienvenue, <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
        </div>

        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Tableau de Bord</a></li>
                <li><a href="modules.php">Mes Modules</a></li>
                <li><a href="../../auth/logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <div class="form-module">
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
                    <label for="course_id">Cours</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Sélectionnez un cours</option>
                        <?php foreach ($cours as $cour): ?>
                            <option value="<?= $cour['id'] ?>"><?= htmlspecialchars($cour['titre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="titre">Titre du Module</label>
                    <input type="text" id="titre" name="titre" 
                           value="<?= htmlspecialchars($titre) ?>" 
                           required placeholder="Ex: Introduction à la Programmation">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              rows="5" required 
                              placeholder="Décrivez brièvement le contenu et les objectifs du module"><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="niveau">Niveau</label>
                    <select id="niveau" name="niveau" required>
                        <option value="">Sélectionnez le niveau</option>
                        <option value="Débutant" <?= $niveau === 'Débutant' ? 'selected' : '' ?>>Débutant</option>
                        <option value="Intermédiaire" <?= $niveau === 'Intermédiaire' ? 'selected' : '' ?>>Intermédiaire</option>
                        <option value="Avancé" <?= $niveau === 'Avancé' ? 'selected' : '' ?>>Avancé</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Créer le Module</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>