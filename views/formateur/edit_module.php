<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([2]); 

$user = $_SESSION['user'];

$module_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$titre = '';
$description = '';
$niveau = '';
$erreurs = [];
$module = null;

try {

    $cours_stmt = $pdo->prepare("
        SELECT id, titre 
        FROM cours 
        WHERE formateur_id = ?
    ");
    $cours_stmt->execute([$user['id']]);
    $cours = $cours_stmt->fetchAll();

    $verif_stmt = $pdo->prepare("
        SELECT m.id, m.titre, m.description, m.course_id, m.ordre, 
               c.titre as cours_titre
        FROM modules m
        JOIN cours c ON m.course_id = c.id
        WHERE m.id = ? AND c.formateur_id = ?
    ");
    $verif_stmt->execute([$module_id, $user['id']]);
    $module = $verif_stmt->fetch();

    if (!$module) {
        $_SESSION['message_erreur'] = "Vous n'avez pas le droit de modifier ce module.";
        header("Location: modules.php");
        exit();
    }

    $titre = $module['titre'];
    $description = $module['description'];
    $course_id = $module['course_id'];
    $ordre = $module['ordre'];

} catch (PDOException $e) {
    logError("Erreur de récupération du module : " . $e->getMessage());
    $_SESSION['message_erreur'] = "Une erreur est survenue : " . $e->getMessage();
    header("Location: modules.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!CSRFToken::verifyToken($_POST['csrf_token'])) {
        die("Erreur de sécurité : Token CSRF invalide");
    }

    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $course_id = trim($_POST['course_id'] ?? '');
    $ordre = trim($_POST['ordre'] ?? 0);

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
                UPDATE modules 
                SET titre = ?, description = ?, course_id = ?, ordre = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $titre, 
                $description, 
                $course_id,
                $ordre,
                $module_id
            ]);

            $_SESSION['message_succes'] = "Le module a été modifié avec succès !";
            header("Location: modules.php");
            exit();

        } catch (PDOException $e) {
            logError("Erreur de modification de module : " . $e->getMessage());
            $erreurs[] = "Une erreur est survenue lors de la modification du module.";
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
    <title>Modifier un Module - Formateur</title>
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

        .btn-delete {
            background-color: #dc3545;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Modifier le Module</h1>
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
                    <label for="course_id">Cours</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Sélectionnez un cours</option>
                        <?php foreach ($cours as $cours_item): ?>
                            <option value="<?= $cours_item['id'] ?>" <?= $course_id === $cours_item['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cours_item['titre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ordre">Ordre</label>
                    <input type="number" id="ordre" name="ordre" 
                           value="<?= htmlspecialchars($ordre) ?>" 
                           required placeholder="Ex: 1">
                </div>

                <button type="submit" class="btn-submit">Modifier le Module</button>

                <a href="delete_module.php?id=<?= $module_id ?>" 
                   class="btn btn-submit btn-delete"
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce module ? Cette action est irréversible.');">
                    Supprimer le Module
                </a>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Système de Gestion de Formations en Ligne. Tous droits réservés.</p>
    </footer>
</body>
</html>