<?php
/* Config */
define('DB_HOST', 'localhost');
define('DB_NAME', 'fastaid');
define('DB_USER', 'fastaid_user');
define('DB_PASS', 'fastaid_pass');
define('SITE_URL', '/');
define('AVG_SPEED_KMH', 25);          // volunteer travel speed for ETA
define('MATCH_RADIUS_KM', 10);       // default match radius
define('SESSION_NAME', 'fastaid_session');
define('LOG_FILE', dirname(__DIR__) . '/logs/app.log');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE);
?>
