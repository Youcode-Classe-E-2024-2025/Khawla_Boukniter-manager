<?php
class User {
    private $pdo;
    private $table = 'users';

    public function __construct($db) {
        $this->pdo = $db;
    }

    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Vérifier si l'utilisateur est banni
            if ($user['is_banned'] == 1) {
                return false; // Connexion refusée si l'utilisateur est banni
            }
            return $user;
        }
        return false;
    }

    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    public function register($nom, $prenom, $email, $password, $role_id) {
        try {

            if ($this->emailExists($email)) {
                return false;
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->pdo->prepare("
                INSERT INTO users 
                (nom, prenom, email, password, role_id, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $result = $stmt->execute([
                $nom, 
                $prenom, 
                $email, 
                $hashed_password, 
                $role_id
            ]);

            return $result;
        } catch (PDOException $e) {

            error_log("Erreur d'inscription : " . $e->getMessage());
            return false;
        }
    }

    public function verifyAdminCredentials($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND role_id = 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            return $admin;
        }
        return false;
    }

    public function verifyEmail($token) {
        return false;
    }

    public function resendVerificationEmail($email) {
        return false;
    }
}