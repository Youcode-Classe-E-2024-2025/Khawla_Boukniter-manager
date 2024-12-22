<?php
require_once '../connexion.php';
require_once '../models/user.php';
require_once '../includes/csrf.php';
require_once '../includes/validation.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role_id = 3; 

    $errors = [];

    if (!Validation::validateName($nom)) {
        $errors[] = "Nom invalide. Utilisez uniquement des lettres et des espaces.";
    }
    if (!Validation::validateName($prenom)) {
        $errors[] = "Prénom invalide. Utilisez uniquement des lettres et des espaces.";
    }

    if (!$email) {
        $errors[] = "Email invalide";
    }

    if (!Validation::validatePassword($password)) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    if (empty($errors)) {
        try {

            CSRFToken::verifyToken($_POST['csrf_token']);

            $userModel = new User($pdo);

            if ($userModel->emailExists($email)) {
                $errors[] = "Un compte existe déjà avec cet email.";
            } else {

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $verification_token = null;

                $stmt = $pdo->prepare("
                    INSERT INTO users 
                    (nom, prenom, email, password, role_id, is_active) 
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $nom, 
                    $prenom, 
                    $email, 
                    $hashed_password, 
                    $role_id
                ]);

                header("Location: login.php?message=Votre compte a été créé avec succès.");
                exit();
            }
        } catch (Exception $e) {
            $errors[] = "Une erreur s'est produite : " . $e->getMessage();
        }
    }

    $error = implode('<br>', $errors);
}

$csrf_token = CSRFToken::generateToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="register-container">
        <form method="POST" class="register-form">
            <h2>Inscription</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required 
                       value="<?= htmlspecialchars($nom ?? '') ?>"
                       pattern="[A-Za-zÀ-ÿ\s-]+" 
                       title="Utilisez uniquement des lettres et des espaces">
            </div>

            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" required 
                       value="<?= htmlspecialchars($prenom ?? '') ?>"
                       pattern="[A-Za-zÀ-ÿ\s-]+" 
                       title="Utilisez uniquement des lettres et des espaces">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($email ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required 
                       minlength="8"
                       pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                       title="Au moins 8 caractères, une majuscule, un chiffre et un caractère spécial">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn-submit">S'inscrire</button>

            <div class="login-link">
                Déjà un compte ? <a href="login.php">Connectez-vous</a>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        confirmPasswordInput.addEventListener('input', function() {
            if (passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        });
    });
    </script>
</body>
</html>