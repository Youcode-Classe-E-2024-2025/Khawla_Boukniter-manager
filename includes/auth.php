<?php
function checkAccess($allowed_roles = [])
{
    if (!isset($_SESSION['user'])) {
        header("Location: ../../auth/login.php");
        exit();
    }

    if (!empty($allowed_roles) && !in_array($_SESSION['user']['role_id'], $allowed_roles)) {

        header("Location: ../../auth/login.php");
        exit();
    }
}

function isAdmin()
{
    return isset($_SESSION['user']) && $_SESSION['user']['role_id'] == 1;
}

function isFormateur()
{
    return isset($_SESSION['user']) && $_SESSION['user']['role_id'] == 2;
}

function isEtudiant()
{
    return isset($_SESSION['user']) && $_SESSION['user']['role_id'] == 3;
}
