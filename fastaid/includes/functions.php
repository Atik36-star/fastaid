<?php
/* Shared helpers */

function sanitize($str) {
  return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function generate_request_id() {
  return 'r' . str_pad(random_int(100, 99999), 5, '0', STR_PAD_LEFT);
}

function initials($name) {
  $parts = explode(' ', trim($name));
  $out = '';
  foreach ($parts as $p) { if ($p) $out .= strtoupper($p[0]); }
  return substr($out, 0, 2);
}

function haversine($lat1, $lon1, $lat2, $lon2) {
  $r = 6371;
  $dLat = deg2rad($lat2 - $lat1);
  $dLon = deg2rad($lon2 - $lon1);
  $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
  return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function eta_minutes($distance_km) {
  $speed = defined('AVG_SPEED_KMH') ? AVG_SPEED_KMH : 25;
  return round(($distance_km / $speed) * 60);
}

function log_error($msg) {
  $file = defined('LOG_FILE') ? LOG_FILE : dirname(__DIR__) . '/logs/app.log';
  $dir = dirname($file);
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  error_log('[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", 3, $file);
}
?>
