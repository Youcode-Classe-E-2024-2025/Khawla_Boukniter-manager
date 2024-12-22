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

    // Vérifier les colonnes de la table users
    $columns_stmt = $pdo->query("SHOW COLUMNS FROM users");
    $columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
    custom_log("Colonnes de la table users : " . implode(', ', $columns));

    // Déterminer la colonne à utiliser pour le bannissement
    $ban_column = in_array('is_banned', $columns) ? 'is_banned' : 
                  (in_array('status', $columns) ? 'status' : null);

    if (!$ban_column) {
        throw new Exception("Aucune colonne de bannissement trouvée.");
    }

    $has_banned_at = in_array('banned_at', $columns);
    custom_log("Colonne banned_at existe : " . ($has_banned_at ? 'Oui' : 'Non'));

    $check_stmt = $pdo->prepare("
        SELECT id, nom, prenom, role_id, email, 
               $ban_column as ban_status
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

    $new_banned_status = ($action === 'ban') ? 
        ($ban_column === 'is_banned' ? 1 : 'inactive') : 
        ($ban_column === 'is_banned' ? 0 : 'active');

    $action_text = ($action === 'ban') ? 'banni' : 'débanni';

    custom_log("Colonne utilisée pour le bannissement : $ban_column");
    custom_log("Nouveau statut de bannissement : $new_banned_status");

    // Construire la requête de mise à jour dynamiquement
    $update_query = "UPDATE users SET $ban_column = ?";
    $update_params = [$new_banned_status];

    if ($has_banned_at) {
        $update_query .= ", banned_at = CASE WHEN ? = " . 
            ($ban_column === 'is_banned' ? "1" : "'inactive'") . 
            " THEN NOW() ELSE NULL END";
        $update_params[] = $new_banned_status;
    }

    $update_query .= " WHERE id = ?";
    $update_params[] = $user_id;

    $update_stmt = $pdo->prepare($update_query);
    
    custom_log("Requête de mise à jour : $update_query");
    custom_log("Paramètres de mise à jour : " . print_r($update_params, true));
    
    $result = $update_stmt->execute($update_params);

    custom_log("Résultat de la mise à jour : " . ($result ? 'Succès' : 'Échec'));
    custom_log("Requête SQL : " . $update_stmt->queryString);
    custom_log("Erreurs PDO : " . print_r($update_stmt->errorInfo(), true));
    custom_log("Nombre de lignes affectées : " . $update_stmt->rowCount());
    
    $verify_query = "SELECT $ban_column as ban_status";
    if ($has_banned_at) {
        $verify_query .= ", banned_at";
    }
    $verify_query .= " FROM users WHERE id = ?";
    
    $verify_stmt = $pdo->prepare($verify_query);
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
