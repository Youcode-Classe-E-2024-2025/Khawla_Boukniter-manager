<?php
require_once '../connexion.php';
require_once '../models/user.php';
require_once '../includes/csrf.php';
require_once '../includes/validation.php';

$error = '';
$success = '';

$roles_stmt = $pdo->query("SELECT id, nom FROM roles WHERE id != 1");
$roles = $roles_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : null;

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

    $role_valid = false;
    foreach ($roles as $role) {
        if ($role['id'] == $role_id) {
            $role_valid = true;
            break;
        }
    }
    if (!$role_valid) {
        $errors[] = "Rôle sélectionné invalide";
    }

    if (empty($errors)) {
        try {
            CSRFToken::verifyToken($_POST['csrf_token']);

            $userModel = new User($pdo);

            if ($userModel->emailExists($email)) {
                $errors[] = "Un compte existe déjà avec cet email.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

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
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/sweet_alerts.js"></script>
    <script src="../assets/js/register.js"></script>
    <style>
        .steps-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 15px;
            font-weight: bold;
            color: #666;
            transition: all 0.3s ease;
        }
        .step.active {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }
        .step-content {
            display: none;
        }
        .step-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        #form-errors {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <form method="POST" class="auth-form register-form">
            <h2>Inscription</h2>

            <div id="form-errors"><?= $error ?></div>

            <div class="steps-container">
                <div class="step active">1</div>
                <div class="step">2</div>
            </div>

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="step-content active">
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" required 
                           value="<?= htmlspecialchars($nom ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" required 
                           value="<?= htmlspecialchars($prenom ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($email ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required 
                           minlength="8">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           minlength="8">
                </div>

                <button type="button" class="btn btn-next">Suivant</button>
            </div>

            <div class="step-content">
                <div class="form-group">
                    <label for="role_id">Choisissez votre rôle</label>
                    <select id="role_id" name="role_id" required>
                        <option value="">Sélectionnez un rôle</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>">
                                <?= htmlspecialchars(ucfirst($role['nom'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Résumé de votre inscription</label>
                    <div class="summary-container">
                        <p><strong>Nom :</strong> <span id="summary-nom"></span></p>
                        <p><strong>Prénom :</strong> <span id="summary-prenom"></span></p>
                        <p><strong>Email :</strong> <span id="summary-email"></span></p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-prev">Précédent</button>
                    <button type="submit" class="btn btn-submit">S'inscrire</button>
                </div>
            </div>

            <div class="login-link">
                Déjà un compte ? <a href="login.php">Connectez-vous</a>
            </div>
        </form>
    </div>

    <script>
        // Mettre à jour le résumé dynamiquement
        document.querySelector('.btn-next').addEventListener('click', function() {
            document.getElementById('summary-nom').textContent = document.getElementById('nom').value;
            document.getElementById('summary-prenom').textContent = document.getElementById('prenom').value;
            document.getElementById('summary-email').textContent = document.getElementById('email').value;
        });
    </script>
</body>
</html>