<?php
class CSRFToken {
    public static function generateToken() {

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateToken($token) {

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function verifyToken($token) {

        try {
            if (!self::validateToken($token)) {
                throw new Exception("Invalid CSRF token");
            }
            return true;
        } catch (Exception $e) {

            error_log("CSRF Token Error: " . $e->getMessage());
            return false;
        }
    }

    public static function insertTokenField() {
        echo '<input type="hidden" name="csrf_token" value="' . self::generateToken() . '">';
    }

    public static function resetToken() {
        unset($_SESSION['csrf_token']);
    }
}