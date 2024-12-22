<?php
session_start();
$error_message = $_SESSION['error_message'] ?? "Une erreur inconnue s'est produite.";
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Erreur</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            text-align: center; 
            padding: 50px; 
        }
        .error-container {
            background-color: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 5px;
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Oups ! Une erreur est survenue</h1>
        <p><?= htmlspecialchars($error_message) ?></p>
        <a href="../auth/login.php">Retour à la connexion</a>
    </div>
</body>
</html>