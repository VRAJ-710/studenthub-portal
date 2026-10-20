<?php
session_start();
$timeout = 1800;

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: index.php?msg=timeout");
    exit;
}
$_SESSION['last_activity'] = time();

if ($_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}
?>
