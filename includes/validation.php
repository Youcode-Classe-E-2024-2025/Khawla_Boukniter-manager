<?php
class Validation {

    public static function validateName($name) {

        return preg_match('/^[A-Za-zÀ-ÿ\s-]{2,50}$/', $name) === 1;
    }

    public static function validatePassword($password) {

        return preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password) === 1;
    }
}
