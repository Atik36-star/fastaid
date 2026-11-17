<?php
require_once __DIR__ . '/config.php';
function is_logged_in() { return !empty($_SESSION['user_id']); }
function current_user_id() { return $_SESSION['user_id'] ?? null; }
function current_role() { return $_SESSION['role'] ?? null; }
?>
