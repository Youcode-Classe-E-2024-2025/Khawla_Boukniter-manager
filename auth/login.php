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
                $user = $userModel->login($email, $password);

                if ($user) {
                    session_regenerate_id(true);

                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'nom' => $user['nom'],
                        'prenom' => $user['prenom'],
                        'role_id' => $user['role_id'],
                        'email' => $user['email']
                    ];

                    if ($user['role_id'] === 1) {
                        $_SESSION['is_admin'] = true;
                    }

                    switch ($user['role_id']) {
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
                } else {
                    // Vérifier si l'utilisateur est banni
                    $stmt = $pdo->prepare("SELECT is_banned FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user_status = $stmt->fetch(PDO::FETCH_COLUMN);
                    
                    if ($user_status === '1') {
                        $error = "Votre compte a été banni. Veuillez contacter l'administrateur.";
                    } else {
                        $error = "Email ou mot de passe incorrect";
                    }
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <form method="POST" class="login-form">
            <h2>Connexion</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    placeholder="Votre email"
                    value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="Votre mot de passe">
            </div>

            <button type="submit" class="btn btn-primary">Se connecter</button>

            <div class="form-footer">
                <a href="register.php">Pas de compte ? Inscrivez-vous</a>
                <a href="reset-password.php">Mot de passe oublié ?</a>
            </div>
        </form>
    </div>

    <script src="../assets/js/login.js"></script>
</body>
</html>