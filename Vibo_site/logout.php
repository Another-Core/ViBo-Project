<?php
    require_once __DIR__ . '/common.php';
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
exit;
