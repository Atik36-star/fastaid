<?php
require_once __DIR__ . '/config.php';
session_name(SESSION_NAME);
session_start();
require_once __DIR__ . '/auth.php';

if (!is_logged_in()) {
    header('Location: /login.html');
    exit;
}

function require_role($role) {
    if (current_role() !== $role) {
        header('Location: /index.html');
        exit;
    }
}
?>
