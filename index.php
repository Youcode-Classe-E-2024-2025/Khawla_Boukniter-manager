<?php
session_start();

if (isset($_SESSION['user'])) {
    switch ($_SESSION['user']['role_id']) {
        case 1:
            header("Location: views/admin/dashboard.php");
            break;
        case 2:
            header("Location: views/formateur/dashboard.php");
            break;
        case 3:
            header("Location: views/etudiant/dashboard.php");
            break;
        default:
            header("Location: auth/login.php");
    }
    exit();
}

header("Location: auth/login.php");
exit();
?>