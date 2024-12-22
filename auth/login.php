<?php

require_once '../connexion.php';
require_once '../models/user.php';
require_once '../includes/csrf.php';

$error = '';
$csrf_token = CSRFToken::generateToken(); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $submitted_token = $_POST['csrf_token'] ?? null;

    if (!$email) {
        $error = "Format d'email invalide";
    } elseif (empty($password)) {
        $error = "Le mot de passe est requis";
    } elseif (empty($submitted_token)) {
        $error = "Token CSRF manquant";
    } else {
        try {
            if (!CSRFToken::verifyToken($submitted_token)) {
                $error = "Token de sécurité invalide. Veuillez réessayer.";
            } else {
                $userModel = new User($pdo);
                
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user_info) {
                    if (password_verify($password, $user_info['password'])) {
                        if ($user_info['is_banned'] == 1) {
                            $banned_at = new DateTime($user_info['banned_at']);
                            $formatted_date = $banned_at->format('d/m/Y à H:i');
                            
                            $prenom = trim($user_info['prenom']) ?: 'Utilisateur';
                            $nom = trim($user_info['nom']) ?: '';
                            
                            $error = "Votre compte a été banni le {$formatted_date}.";
                            
                            $_SESSION['ban_details'] = sprintf(
                                "<div class='ban-message'>
                                    <h3>Compte Suspendu</h3>
                                    <p>Bonjour %s %s,</p>
                                    <p>Votre compte a été banni le <strong>%s</strong>.</p>
                                    <p>Motifs possibles :</p>
                                    <ul>
                                        <li>Violation des conditions d'utilisation</li>
                                        <li>Comportement inapproprié</li>
                                        <li>Activités suspectes détectées</li>
                                    </ul>
                                    <p>Pour plus d'informations, veuillez contacter l'administrateur.</p>
                                    <p>Email de contact : admin@formation.com</p>
                                </div>", 
                                htmlspecialchars($prenom), 
                                htmlspecialchars($nom), 
                                $formatted_date
                            );
                        } else {
                            session_regenerate_id(true);

                            $_SESSION['user'] = [
                                'id' => $user_info['id'],
                                'nom' => $user_info['nom'],
                                'prenom' => $user_info['prenom'],
                                'role_id' => $user_info['role_id'],
                                'email' => $user_info['email']
                            ];

                            if ($user_info['role_id'] === 1) {
                                $_SESSION['is_admin'] = true;
                            }

                            switch ($user_info['role_id']) {
                                case 1:
                                    header("Location: ../views/admin/dashboard.php");
                                    break;
                                case 2:
                                    header("Location: ../views/formateur/dashboard.php");
                                    break;
                                case 3:
                                    header("Location: ../views/etudiant/dashboard.php");
                                    break;
                                default:
                                    header("Location: ../index.php");
                            }
                            exit();
                        }
                    } else {
                        $error = "Email ou mot de passe incorrect";
                    }
                } else {
                    $error = "Email ou mot de passe incorrect";
                }
            }
        } catch (Exception $e) {
            $error = "Une erreur s'est produite. Veuillez réessayer.";
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/sweet_alerts.js"></script>
</head>
<body>
    <div class="auth-container">
        <form method="POST" class="auth-form login-form">
            <h2>Connexion</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['message'])): ?>
                <div class="success-message">
                    <?= htmlspecialchars($_GET['message']) ?>
                </div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($email ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Se connecter</button>

            <div class="register-link">
                Pas de compte ? <a href="register.php">Inscrivez-vous</a>
            </div>
        </form>
    </div>
</body>
</html>