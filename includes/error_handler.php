<?php

function displayUserError($message)
{
    session_start();
    $_SESSION['error_message'] = $message;
    header("Location: ../views/error.php");
    exit();
}
?>
