<?php
require_once __DIR__ . '/config.php';

function csrf_token() {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
  }
  if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
  return $_SESSION['csrf'];
}

function csrf_validate($token) {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
  }
  return isset($_SESSION['csrf']) && !empty($token) && hash_equals($_SESSION['csrf'], $token);
}
?>
