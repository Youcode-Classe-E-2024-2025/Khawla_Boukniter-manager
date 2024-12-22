<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/auth.php';
require_once '../../connexion.php';
require_once '../../includes/error_handler.php';

checkAccess([1]);

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$log_file = '../../ban_user_debug.log';

function custom_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

custom_log("Début du processus de bannissement - User ID: $user_id, Action: $action");

if ($user_id <= 0 || !in_array($action, ['ban', 'unban'])) {
    custom_log("ERREUR : Paramètres invalides - User ID: $user_id, Action: $action");
    $_SESSION['error'] = "Paramètres invalides.";
    header('Location: users.php');
    exit();
}

try {
    $pdo->beginTransaction();

    $check_stmt = $pdo->prepare("
        SELECT id, nom, prenom, role_id, is_banned, email 
        FROM users 
        WHERE id = ?
    ");
    $check_execute_result = $check_stmt->execute([$user_id]);
    
    custom_log("Résultat de l'exécution de la requête de vérification : " . ($check_execute_result ? 'Succès' : 'Échec'));
    
    $user = $check_stmt->fetch(PDO::FETCH_ASSOC);

    custom_log("Détails de l'utilisateur : " . print_r($user, true));

    if (!$user) {
        custom_log("ERREUR : Utilisateur non trouvé pour l'ID $user_id");
        throw new Exception("Utilisateur non trouvé.");
    }

    if ($user['role_id'] == 1) {
        custom_log("ERREUR : Tentative de bannir un administrateur");
        throw new Exception("Impossible de bannir un administrateur.");
    }

    $new_banned_status = ($action === 'ban') ? 1 : 0;
    $action_text = ($action === 'ban') ? 'banni' : 'débanni';

    custom_log("Nouveau statut de bannissement : $new_banned_status");

    $update_stmt = $pdo->prepare("
        UPDATE users 
        SET is_banned = ?, 
            banned_at = CASE WHEN ? = 1 THEN NOW() ELSE NULL END
        WHERE id = ?
    ");
    
    custom_log("Paramètres de mise à jour : " . print_r([$new_banned_status, $new_banned_status, $user_id], true));
    
    $result = $update_stmt->execute([$new_banned_status, $new_banned_status, $user_id]);

    custom_log("Résultat de la mise à jour : " . ($result ? 'Succès' : 'Échec'));
    custom_log("Requête SQL : " . $update_stmt->queryString);
    custom_log("Erreurs PDO : " . print_r($update_stmt->errorInfo(), true));
    custom_log("Nombre de lignes affectées : " . $update_stmt->rowCount());
    
    // Vérifier l'état actuel de l'utilisateur après la mise à jour
    $verify_stmt = $pdo->prepare("
        SELECT is_banned, banned_at 
        FROM users 
        WHERE id = ?
    ");
    $verify_stmt->execute([$user_id]);
    $verified_user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    custom_log("État de l'utilisateur après mise à jour : " . print_r($verified_user, true));
    
    if (!$result || $update_stmt->rowCount() == 0) {
        custom_log("ERREUR : Échec de la mise à jour du statut de bannissement");
        throw new Exception("Échec de la mise à jour du statut de bannissement : " . implode(', ', $update_stmt->errorInfo()));
    }

    $pdo->commit();

    custom_log("Utilisateur {$action_text} : {$user['nom']} {$user['prenom']} (ID: $user_id, Email: {$user['email']})");

    $_SESSION['success'] = "Utilisateur " . $action_text . " avec succès.";
    
    header('Location: users.php');
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    custom_log("ERREUR CRITIQUE de bannissement d'utilisateur : " . $e->getMessage());
    custom_log("Trace de l'erreur : " . print_r($e->getTrace(), true));

    $_SESSION['error'] = $e->getMessage();
    header('Location: users.php');
    exit();
}
?>
