<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';
require_once '../../includes/csrf.php';

checkAccess([1, 2]); 

$user = $_SESSION['user'];

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$confirm = isset($_GET['confirm']) ? (bool)$_GET['confirm'] : false;

if (!isset($_GET['csrf_token']) || !CSRFToken::verifyToken($_GET['csrf_token'])) {
    $_SESSION['message_erreur'] = "Erreur de sécurité : Token CSRF invalide";
    header("Location: courses.php");
    exit();
}

try {

    $pdo->beginTransaction();

    $verif_stmt = $pdo->prepare("
        SELECT id, titre 
        FROM cours 
        WHERE id = ? AND formateur_id = ?
    ");
    $verif_stmt->execute([$course_id, $user['id']]);
    $cours = $verif_stmt->fetch();

    if (!$cours) {
        $_SESSION['message_erreur'] = "Vous n'avez pas le droit de désactiver ce cours.";
        header("Location: courses.php");
        exit();
    }

    $modules_stmt = $pdo->prepare("
        SELECT id, titre 
        FROM modules 
        WHERE course_id = ? AND is_archived = false
    ");
    $modules_stmt->execute([$course_id]);
    $modules = $modules_stmt->fetchAll();

    $inscriptions_stmt = $pdo->prepare("
        SELECT id, user_id 
        FROM inscriptions 
        WHERE course_id = ?
    ");
    $inscriptions_stmt->execute([$course_id]);
    $inscriptions = $inscriptions_stmt->fetchAll();

    if ((!empty($modules) || !empty($inscriptions)) && !$confirm) {

        $modules_details = implode(", ", array_column($modules, 'titre'));
        $inscriptions_count = count($inscriptions);

        $csrf_token = CSRFToken::generateToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmation de désactivation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .warning { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; }
        .actions { margin-top: 20px; display: flex; justify-content: space-between; }
        .btn { 
            padding: 10px 15px; 
            text-decoration: none; 
            border-radius: 5px; 
            cursor: pointer;
        }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="warning">
        <h2>Attention : Désactivation de cours</h2>
        <p>Vous êtes sur le point de désactiver le cours "<?= htmlspecialchars($cours['titre']) ?>".</p>

        <?php if (!empty($modules)): ?>
        <p>Ce cours contient les modules suivants qui seront également désactivés :</p>
        <ul>
            <?php foreach ($modules as $module): ?>
                <li><?= htmlspecialchars($module['titre']) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if (!empty($inscriptions)): ?>
        <p>Ce cours a <?= $inscriptions_count ?> inscription(s) qui seront également désactivées.</p>
        <?php endif; ?>

        <div class="actions">
            <a href="courses.php" class="btn btn-secondary">Annuler</a>
            <a href="?id=<?= $course_id ?>&confirm=1&csrf_token=<?= $csrf_token ?>" class="btn btn-danger">Confirmer la désactivation</a>
        </div>
    </div>
</body>
</html>
<?php
        exit();
    }

    $update_cours_stmt = $pdo->prepare("UPDATE cours SET is_active = false WHERE id = ?");
    $update_cours_stmt->execute([$course_id]);

    $update_modules_stmt = $pdo->prepare("UPDATE modules SET is_archived = true WHERE course_id = ?");
    $update_modules_stmt->execute([$course_id]);

    $update_inscriptions_stmt = $pdo->prepare("
        UPDATE inscriptions 
        SET status = 'refuse' 
        WHERE course_id = ?
    ");
    $update_inscriptions_stmt->execute([$course_id]);

    $pdo->commit();

    $_SESSION['message_succes'] = "Le cours \"" . htmlspecialchars($cours['titre']) . "\" a été désactivé avec tous ses modules et inscriptions.";
    header("Location: courses.php");
    exit();

} catch (PDOException $e) {

    $pdo->rollBack();

    logError("Erreur de désactivation du cours : " . $e->getMessage());
    $_SESSION['message_erreur'] = "Une erreur est survenue lors de la désactivation du cours.";
    header("Location: courses.php");
    exit();
}
