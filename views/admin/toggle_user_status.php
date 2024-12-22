<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    $_SESSION['error_message'] = "ID utilisateur non spécifié.";
    header('Location: dashboard.php');
    exit();
}

$user_id = intval($_GET['user_id']);

try {
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error_message'] = "Utilisateur non trouvé.";
        header('Location: dashboard.php');
        exit();
    }

    $new_status = ($user['status'] == 'active') ? 'inactive' : 'active';

    $update_stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $update_stmt->execute([$new_status, $user_id]);

    $_SESSION['success_message'] = "Statut de l'utilisateur mis à jour avec succès.";
} catch (PDOException $e) {
    error_log("Erreur de mise à jour du statut utilisateur : " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur lors de la mise à jour du statut.";
}

header('Location: dashboard.php');
exit();
?>
