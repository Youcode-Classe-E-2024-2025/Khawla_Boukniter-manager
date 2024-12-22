<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'create_user':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nom = $_POST['nom'] ?? '';
                $prenom = $_POST['prenom'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $role_id = $_POST['role_id'] ?? null;

                $errors = [];
                if (empty($nom)) $errors[] = "Le nom est requis.";
                if (empty($prenom)) $errors[] = "Le prénom est requis.";
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
                if (empty($password)) $errors[] = "Le mot de passe est requis.";
                if (empty($role_id)) $errors[] = "Le rôle est requis.";

                if (empty($errors)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        INSERT INTO users 
                        (nom, prenom, email, password, role_id, status, date_creation) 
                        VALUES (?, ?, ?, ?, ?, 'active', NOW())
                    ");
                    $stmt->execute([$nom, $prenom, $email, $hashed_password, $role_id]);

                    $_SESSION['success_message'] = "Utilisateur créé avec succès.";
                    header('Location: users.php');
                    exit();
                } else {
                    $_SESSION['error_messages'] = $errors;
                    header('Location: create_user.php');
                    exit();
                }
            }
            break;

        case 'update_user':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $user_id = $_POST['user_id'] ?? null;
                $nom = $_POST['nom'] ?? '';
                $prenom = $_POST['prenom'] ?? '';
                $email = $_POST['email'] ?? '';
                $role_id = $_POST['role_id'] ?? null;

                $errors = [];
                if (empty($user_id)) $errors[] = "ID utilisateur manquant.";
                if (empty($nom)) $errors[] = "Le nom est requis.";
                if (empty($prenom)) $errors[] = "Le prénom est requis.";
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
                if (empty($role_id)) $errors[] = "Le rôle est requis.";

                if (empty($errors)) {
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET nom = ?, prenom = ?, email = ?, role_id = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$nom, $prenom, $email, $role_id, $user_id]);

                    $_SESSION['success_message'] = "Profil utilisateur mis à jour avec succès.";
                    header('Location: users.php');
                    exit();
                } else {
                    $_SESSION['error_messages'] = $errors;
                    header('Location: edit_user.php?id=' . $user_id);
                    exit();
                }
            }
            break;

        case 'delete_user':
            $user_id = $_GET['user_id'] ?? null;

            if ($user_id) {
                $stmt = $pdo->prepare("UPDATE users SET status = 'deleted' WHERE id = ?");
                $stmt->execute([$user_id]);

                $_SESSION['success_message'] = "Utilisateur supprimé avec succès.";
                header('Location: users.php');
                exit();
            }
            break;

        case 'reset_password':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $user_id = $_POST['user_id'] ?? null;
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                $errors = [];
                if (empty($new_password)) $errors[] = "Nouveau mot de passe requis.";
                if ($new_password !== $confirm_password) $errors[] = "Les mots de passe ne correspondent pas.";

                if (empty($errors)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET password = ?, password_reset_date = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$hashed_password, $user_id]);

                    $_SESSION['success_message'] = "Mot de passe réinitialisé avec succès.";
                    header('Location: users.php');
                    exit();
                } else {
                    $_SESSION['error_messages'] = $errors;
                    header('Location: reset_password.php?id=' . $user_id);
                    exit();
                }
            }
            break;

        default:
            header('Location: users.php');
            exit();
    }
} catch (PDOException $e) {
    error_log("User Management Error: " . $e->getMessage());
    
    $_SESSION['error_message'] = "Une erreur s'est produite. Veuillez réessayer.";
    header('Location: users.php');
    exit();
}
?>
