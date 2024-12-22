<?php
require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';

// Vérifier l'accès admin
checkAccess([1]);

// Récupérer l'ID de l'utilisateur à supprimer
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    $_SESSION['error'] = "ID d'utilisateur invalide.";
    header('Location: users.php');
    exit();
}

try {
    // Commencer une transaction
    $pdo->beginTransaction();

    // Vérifier si l'utilisateur existe et n'est pas un admin
    $check_stmt = $pdo->prepare("
        SELECT id, nom, prenom, role_id, email 
        FROM users 
        WHERE id = ? AND role_id != 1
    ");
    $check_stmt->execute([$user_id]);
    $user = $check_stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifier l'existence de l'utilisateur
    if (!$user) {
        throw new Exception("Utilisateur non trouvé ou impossible à supprimer.");
    }

    // Supprimer les inscriptions de l'utilisateur
    $delete_inscriptions_stmt = $pdo->prepare("
        DELETE FROM inscriptions 
        WHERE user_id = ?
    ");
    $delete_inscriptions_stmt->execute([$user_id]);

    // Supprimer l'utilisateur
    $delete_user_stmt = $pdo->prepare("
        DELETE FROM users 
        WHERE id = ?
    ");
    $delete_user_stmt->execute([$user_id]);

    // Valider la transaction
    $pdo->commit();

    // Message de succès
    $_SESSION['success'] = "Utilisateur supprimé avec succès.";
    header('Location: users.php');
    exit();

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $pdo->rollBack();

    // Message d'erreur
    $_SESSION['error'] = $e->getMessage();
    header('Location: users.php');
    exit();
}
?>
