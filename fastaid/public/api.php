<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notifications.php';

session_name(SESSION_NAME);
session_start();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// --- REAL-TIME SERVER-SENT EVENTS (SSE) STREAM ---
if ($action === 'stream') {
  $uid = $_SESSION['user_id'] ?? null;
  session_write_close(); // Release session lock so other requests aren't blocked

  header('Content-Type: text/event-stream; charset=utf-8');
  header('Cache-Control: no-cache');
  header('Connection: keep-alive');
  header('X-Accel-Buffering: no');

  set_time_limit(45);

  $rid = $_GET['request_id'] ?? '';
  $last_status = '';
  $last_notif_id = 0;

  function send_sse($event, $data) {
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level() > 0) ob_flush();
    flush();
  }

  $start = time();
  while (time() - $start < 30) {
    if (connection_aborted()) break;

    $db = DB::connect();

    if (!empty($rid)) {
      $r = DB::fetch("SELECT * FROM requests WHERE id=?", [$rid]);
      $v = DB::fetch("SELECT a.*, u.full_name FROM assignments a JOIN users u ON a.volunteer_id=u.id WHERE a.request_id=?", [$rid]);
      if ($r) {
        $cur_status = $r['status'] . '|' . ($v['full_name'] ?? '');
        if ($cur_status !== $last_status) {
          $last_status = $cur_status;
          send_sse('request_update', [
            'id' => $r['id'],
            'status' => $r['status'],
            'type' => $r['emergency_type'],
            'volunteerName' => $v['full_name'] ?? null,
            'updatedAt' => $r['updated_at']
          ]);
        }
      }
    }

    if ($uid) {
      $notifs = DB::fetchAll("SELECT * FROM notifications WHERE user_id=? AND id > ? ORDER BY id ASC", [$uid, $last_notif_id]);
      foreach ($notifs as $n) {
        $last_notif_id = max($last_notif_id, (int)$n['id']);
        send_sse('notification', [
          'id' => $n['id'],
          'title' => $n['title'],
          'message' => $n['message'],
          'createdAt' => $n['created_at']
        ]);
      }
    }

    send_sse('ping', ['time' => time()]);
    sleep(2);
  }
  exit;
}

header('Content-Type: application/json; charset=utf-8');

// CSRF check for POST
if ($method === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $_POST = array_merge($_POST, $body);
  $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf'] ?? '');
  if (!csrf_validate($csrf)) { echo json_encode(['success'=>false,'error'=>'CSRF invalid']); exit; }
}

// Helper to output JSON
function out($data) { echo json_encode($data); exit; }
function ok($d) { out(['success'=>true,'data'=>$d]); }
function err($e, $code=400) { http_response_code($code); out(['success'=>false,'error'=>$e]); }

// --- AUTH ---
if ($action === 'register' && $method === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  $role = in_array($_POST['role'] ?? '', ['patient','volunteer','admin']) ? $_POST['role'] : 'patient';
  if (strlen($email)<3 || strlen($pass)<6 || strlen($name)<2) err('Invalid input');
  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $db = DB::connect();
  try {
    DB::run("INSERT INTO users (full_name,email,password_hash,role) VALUES (?,?,?,?)", [$name,$email,$hash,$role]);
    $uid = $db->lastInsertId();
    if ($role==='volunteer') {
      $db->prepare("INSERT INTO volunteers (user_id,certification_type,certificate_number,years_experience) VALUES (?,?,?,?)")
        ->execute([$uid, $_POST['certification']??'First Aid', $_POST['certNumber']??'', $_POST['yearsExp']??0]);
    }
    $_SESSION['user_id'] = $uid; $_SESSION['role'] = $role;
    ok(['user'=>['id'=>$uid,'name'=>$name,'role'=>$role,'verified'=>($role==='patient')]]);
  } catch (Exception $e) { err('Email exists'); }
}

if ($action === 'login' && $method === 'POST') {
  // Rate limit: max 5 attempts per 15 min per IP / email
  $key = 'login_attempt_' . ($_SERVER['REMOTE_ADDR'] ?? '0') . '_' . (trim($_POST['email'] ?? ''));
  $attempts = $_SESSION[$key] ?? 0;
  $last = $_SESSION[$key . '_time'] ?? 0;
  if (time() - $last > 900) { $attempts = 0; $last = time(); }
  if ($attempts >= 5) { err('Too many attempts. Try again in 15 minutes.', 429); }

  $email = trim($_POST['email'] ?? ''); $pass = $_POST['password'] ?? '';
  $db = DB::connect();
  $row = DB::fetch("SELECT * FROM users WHERE email=?", [$email]);
  if (!$row || !password_verify($pass, $row['password_hash'])) {
    $_SESSION[$key] = ($attempts + 1);
    $_SESSION[$key . '_time'] = time();
    err('Invalid credentials', 401);
  }
  // Clear rate limit on successful login
  unset($_SESSION[$key], $_SESSION[$key . '_time']);
  session_regenerate_id(true);
  $_SESSION['user_id'] = $row['id']; $_SESSION['role'] = $row['role'];
  $v = DB::fetch("SELECT * FROM volunteers WHERE user_id=?", [$row['id']]);
  ok(['user'=>['id'=>$row['id'],'name'=>$row['full_name'],'role'=>$row['role'],'verified'=>($v['verified_status']==='approved'??($row['role']==='patient')),'available'=>($v['is_available']??1)]]);
}

if ($action === 'logout' && $method === 'POST') { session_destroy(); ok([]); }
if ($action === 'get_me') {
  if (empty($_SESSION['user_id'])) err('Not logged in', 401);
  $db = DB::connect();
  $u = DB::fetch("SELECT * FROM users WHERE id=?", [$_SESSION['user_id']]);
  $v = DB::fetch("SELECT * FROM volunteers WHERE user_id=?", [$_SESSION['user_id']]);
  ok(['user'=>['id'=>$u['id'],'name'=>$u['full_name'],'email'=>$u['email'],'role'=>$u['role'],'verified'=>($v['verified_status']==='approved'||$u['role']=='patient'),'available'=>($v['is_available']??1)]]);
}

// --- REQUESTS ---
if ($action === 'create_request' && $method === 'POST') {
  if (empty($_SESSION['user_id'])) err('Login required', 401);
  $type = $_POST['type'] ?? 'Other'; $desc = $_POST['description'] ?? ''; $loc = $_POST['location'] ?? '';
  $lat = floatval($_POST['latitude'] ?? 0); $lng = floatval($_POST['longitude'] ?? 0);
  $db = DB::connect();
  $rid = 'r' . rand(100,999);
  $db->prepare("INSERT INTO requests (id,patient_id,emergency_type,description,latitude,longitude,status) VALUES (?,?,?,?,?,?,?)")
    ->execute([$rid, $_SESSION['user_id'], $type, $desc, $lat, $lng, 'requested']);
  // Match nearby volunteers (Haversine)
  $rad = MATCH_RADIUS_KM;
  $sql = "SELECT v.*, u.full_name, v.skill_tags FROM volunteers v JOIN users u ON v.user_id=u.id WHERE v.verified_status='approved' AND v.is_available=1 AND TIME(NOW()) NOT BETWEEN v.dnd_start AND v.dnd_end";
  $vols = $db->query($sql)->fetchAll();
  $matches = []; foreach ($vols as $v) {
    if ($v['latitude']&&$v['longitude']) {
      $d = haversine($lat,$lng,$v['latitude'],$v['longitude']);
      $tags = strtolower($v['skill_tags'] ?? '');
      $type_l = strtolower($type);
      $skill_bonus = (strpos($tags, $type_l) !== false) ? 5.0 : 0;
      $effective_dist = max(0, $d - $skill_bonus);
      if ($d <= $rad) $matches[] = ['id'=>$v['user_id'],'name'=>$v['full_name'],'dist'=>$d,'score'=>$effective_dist,'lat'=>$v['latitude'],'lng'=>$v['longitude']];
    }
  }
  usort($matches, fn($a,$b)=>$a['score']<=>$b['score']);
  $top = array_slice($matches,0,10);
  foreach ($top as $m) {
    DB::run("INSERT IGNORE INTO notifications (user_id,title,message) VALUES (?,?,?)", [$m['id'],'New Case','Emergency: '.$type.' nearby ('.round($m['dist'],1).' km)']);

    // Send external notification (email) to volunteer
    $v_email = $db->query("SELECT email FROM users WHERE id=".$m['id'])->fetchColumn();
    if ($v_email) {
      send_email($v_email, 'FastAid: New Emergency Nearby', 'A new emergency ('.$type.') is nearby: '.round($m['dist'],1).' km away. Log in to accept.', $m['id']);
    }
  }
  ok(['requestId'=>$rid,'status'=>'Requested']);
}

if ($action === 'get_request_status') {
  $rid = $_GET['id'] ?? '';
  $db = DB::connect();
  $r = DB::fetch("SELECT * FROM requests WHERE id=?", [$rid]);
  $v = DB::fetch("SELECT a.*, u.full_name FROM assignments a JOIN users u ON a.volunteer_id=u.id WHERE a.request_id=?", [$rid]);
  $data = $r ? ['id'=>$r['id'],'status'=>$r['status'],'type'=>$r['emergency_type'],'volunteerName'=>($v['full_name']??null),'distanceKm'=>null,'startedAt'=>$r['created_at']] : null;
  ok(['request'=>$data]);
}

if ($action === 'get_my_requests') {
  if (empty($_SESSION['user_id'])) err('Login required',401);
  $db = DB::connect();
  $rows = DB::fetchAll("SELECT * FROM requests WHERE patient_id=? ORDER BY created_at DESC", [$_SESSION['user_id']]);
  ok(['requests'=>$rows]);
}

if ($action === 'get_incoming_requests') {
  if (empty($_SESSION['user_id'])) err('Login required',401);
  $db = DB::connect();
  $uid = $_SESSION['user_id'];
  $v = DB::fetch("SELECT id FROM volunteers WHERE user_id=? AND verified_status='approved'", [$uid]);
  if (!$v) err('Not approved volunteer',403);
  $rows = DB::fetchAll("SELECT r.*, u.full_name AS patient_name FROM requests r JOIN users u ON r.patient_id=u.id WHERE r.status='requested'");
  ok(['requests'=>$rows]);
}

if ($action === 'accept_request' && $method === 'POST') {
  if (empty($_SESSION['user_id'])) err('Login required',401);
  $rid = $_POST['id'] ?? '';
  $db = DB::connect();
  // Atomically check status and assign
  $db->beginTransaction();
  $r = DB::fetch("SELECT * FROM requests WHERE id=? FOR UPDATE", [$rid]);
  if (!$r || $r['status'] !== 'requested') { $db->rollBack(); err('Already assigned'); }
  $uid = $_SESSION['user_id'];
  $v = DB::fetch("SELECT * FROM volunteers WHERE user_id=? AND verified_status='approved'", [$uid]);
  if (!$v) { $db->rollBack(); err('Not approved',403); }
  DB::run("UPDATE requests SET status='accepted', updated_at=NOW() WHERE id=?", [$rid]);
  DB::run("INSERT INTO assignments (request_id,volunteer_id) VALUES (?,?)", [$rid,$v['id']]);
  $db->commit();

  // Notify patient via email
  $p_email = $db->query("SELECT email FROM users WHERE id=".$r['patient_id'])->fetchColumn();
  if ($p_email) {
      send_email($p_email, 'FastAid: Volunteer Accepted', 'Your emergency request has been accepted by a volunteer. They are on their way.', $r['patient_id']);
  }

  ok(['request'=>['id'=>$rid,'status'=>'accepted','volunteerName'=>'Volunteer']]);
}

if ($action === 'update_request_status' && $method === 'POST') {
  $rid = $_POST['id'] ?? ''; $new = $_POST['status'] ?? '';
  $allowed = ['requested','accepted','on_the_way','arrived','completed','cancelled'];
  $db = DB::connect();
  $r = DB::fetch("SELECT * FROM requests WHERE id=?", [$rid]);
  if (!$r) err('Not found');
  $order = ['requested','accepted','on_the_way','arrived','completed','cancelled'];
  $curr = array_search($r['status'],$order); $next = array_search($new,$order);
  if ($curr===false || $next===false || $next < $curr || $next > $curr+1) err('Invalid status jump');
  DB::run("UPDATE requests SET status=? WHERE id=?", [$new,$rid]);
  // record arrival/completion times in assignments
  if ($new==='arrived') DB::run("UPDATE assignments SET arrived_at=NOW() WHERE request_id=?", [$rid]);
  if ($new==='completed') DB::run("UPDATE assignments SET completed_at=NOW() WHERE request_id=?", [$rid]);
  ok([]);
}

if ($action === 'update_availability' && $method === 'POST') {
  if (empty($_SESSION['user_id'])) err('Login required',401);
  $db = DB::connect();
  $v = DB::fetch("SELECT * FROM volunteers WHERE user_id=?", [$_SESSION['user_id']]);
  if (!$v) err('Not volunteer',403);
  $avail = $_POST['available'] ? 1 : 0;
  DB::run("UPDATE volunteers SET is_available=? WHERE id=?", [$avail,$v['id']]);
  ok(['available'=>$avail]);
}

if ($action === 'set_dnd_hours' && $method === 'POST') {
  if (empty($_SESSION['user_id'])) err('Login required',401);
  $db = DB::connect();
  $v = DB::fetch("SELECT * FROM volunteers WHERE user_id=?", [$_SESSION['user_id']]);
  if (!$v) err('Not volunteer',403);
  DB::run("UPDATE volunteers SET dnd_start=?, dnd_end=? WHERE id=?", [$_POST['start']??'00:00', $_POST['end']??'00:00', $v['id']]);
  ok([]);
}

if ($action === 'submit_rating' && $method === 'POST') {
  $rid = $_POST['request_id'] ?? ''; $stars = (int)($_POST['stars'] ?? 0);
  $db = DB::connect();
  $r = DB::fetch("SELECT * FROM requests WHERE id=?", [$rid]);
  if (!$r || $r['status']!=='completed') err('Not completed');
  if ($_SESSION['user_id'] ?? 0 != $r['patient_id']) err('Not your request');
  if ($stars<1 || $stars>5) err('Invalid stars');
  $db->prepare("INSERT IGNORE INTO ratings (request_id,patient_id,volunteer_id,stars,comment) SELECT ?,?,?,?,? FROM assignments a WHERE a.request_id=?")
    ->execute([$rid, $r['patient_id'], $r['volunteer_id'] ?? 0, $stars, $_POST['comment'] ?? '', $rid]);
  ok([]);
}

if ($action === 'submit_debrief' && $method === 'POST') {
  $rid = $_POST['request_id'] ?? '';
  $db = DB::connect();
  $a = DB::fetch("SELECT * FROM assignments a JOIN volunteers v ON a.volunteer_id=v.id WHERE a.request_id=?", [$rid]);
  if (!$a) err('No assignment');
  if (($_SESSION['user_id'] ?? 0) != $a['user_id']) err('Not assigned');
  $db->prepare("INSERT IGNORE INTO debriefs (request_id,volunteer_id,what_worked,what_was_hard,what_to_change) VALUES (?,?,?,?,?)")
    ->execute([$rid, $a['volunteer_id'], $_POST['answers'][0] ?? '', $_POST['answers'][1] ?? '', $_POST['answers'][2] ?? '']);
  ok([]);
}

if ($action === 'get_case_report') {
  $rid = $_GET['id'] ?? '';
  if (empty($rid)) err('Request ID required');
  $db = DB::connect();
  $r = DB::fetch("SELECT r.*, u.full_name AS patient_name, u.email AS patient_email FROM requests r JOIN users u ON r.patient_id=u.id WHERE r.id=?", [$rid]);
  if (!$r) err('Case not found', 404);

  $v = DB::fetch("SELECT a.*, u.full_name AS volunteer_name, u.email AS volunteer_email, vol.certification_type, vol.certificate_number, vol.years_experience FROM assignments a JOIN users u ON a.volunteer_id=u.id JOIN volunteers vol ON vol.user_id=u.id WHERE a.request_id=?", [$rid]);
  $rating = DB::fetch("SELECT * FROM ratings WHERE request_id=?", [$rid]);
  $debrief = DB::fetch("SELECT * FROM debriefs WHERE request_id=?", [$rid]);

  ok([
    'report' => [
      'id' => $r['id'],
      'status' => $r['status'],
      'emergencyType' => $r['emergency_type'],
      'description' => $r['description'],
      'latitude' => $r['latitude'],
      'longitude' => $r['longitude'],
      'createdAt' => $r['created_at'],
      'updatedAt' => $r['updated_at'],
      'patient' => [
        'name' => $r['patient_name'],
        'email' => $r['patient_email']
      ],
      'volunteer' => $v ? [
        'name' => $v['volunteer_name'],
        'email' => $v['volunteer_email'],
        'certification' => $v['certification_type'],
        'certNumber' => $v['certificate_number'],
        'yearsExp' => $v['years_experience'],
        'assignedAt' => $v['assigned_at'] ?? null,
        'arrivedAt' => $v['arrived_at'] ?? null,
        'completedAt' => $v['completed_at'] ?? null
      ] : null,
      'rating' => $rating ? [
        'stars' => (int)$rating['stars'],
        'comment' => $rating['comment'],
        'createdAt' => $rating['created_at']
      ] : null,
      'debrief' => $debrief ? [
        'whatWorked' => $debrief['what_worked'],
        'whatWasHard' => $debrief['what_was_hard'],
        'whatToChange' => $debrief['what_to_change'],
        'submittedAt' => $debrief['created_at']
      ] : null
    ]
  ]);
}

if ($action === 'get_notifications') {
  if (empty($_SESSION['user_id'])) err('Login required',401);
  $db = DB::connect();
  $rows = DB::fetchAll("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20", [$_SESSION['user_id']]);
  ok(['notifications'=>$rows]);
}

if ($action === 'mark_read' && $method === 'POST') {
  $db = DB::connect(); DB::run("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?", [$_POST['id'], $_SESSION['user_id'] ?? 0]);
  ok([]);
}

if ($action === 'mark_all_read' && $method === 'POST') {
  $db = DB::connect(); DB::run("UPDATE notifications SET is_read=1 WHERE user_id=?", [$_SESSION['user_id'] ?? 0]);
  ok([]);
}

// --- ADMIN ---
function require_admin() { if (($_SESSION['role'] ?? '') !== 'admin') err('Admin required', 403); }

if ($action === 'admin_stats') { require_admin(); $db = DB::connect(); $s=[
  'activeRequests'=>$db->query("SELECT COUNT(*) FROM requests WHERE status='requested'")->fetchColumn(),
  'volunteers'=>$db->query("SELECT COUNT(*) FROM volunteers WHERE verified_status='approved'")->fetchColumn(),
  'patients'=>$db->query("SELECT COUNT(*) FROM users WHERE role='patient'")->fetchColumn(),
  'pendingVerifications'=>$db->query("SELECT COUNT(*) FROM volunteers WHERE verified_status='pending'")->fetchColumn(),
]; ok($s); }

if ($action === 'admin_users') { require_admin(); $db = DB::connect(); ok(['users'=>$db->query("SELECT u.id,u.full_name,u.email,u.phone,u.role,u.created_at,v.id AS volunteer_id,v.certification_type,v.verified_status FROM users u LEFT JOIN volunteers v ON v.user_id=u.id WHERE u.role IN ('volunteer','patient') ORDER BY u.full_name")->fetchAll()]); }
if ($action === 'admin_pending_volunteers') { require_admin(); $db = DB::connect(); ok(['users'=>$db->query("SELECT v.*, u.full_name, u.email, u.phone, u.created_at AS registered_at FROM volunteers v JOIN users u ON v.user_id=u.id WHERE v.verified_status='pending' ORDER BY u.created_at DESC")->fetchAll()]); }
if ($action === 'admin_verify_volunteer' && $method === 'POST') { require_admin(); $db = DB::connect(); DB::run("UPDATE volunteers SET verified_status='approved' WHERE id=?", [$_POST['id']]); ok([]); }
if ($action === 'admin_reject_volunteer' && $method === 'POST') { require_admin(); $db = DB::connect(); DB::run("UPDATE volunteers SET verified_status='rejected' WHERE id=?", [$_POST['id']]); ok([]); }
if ($action === 'admin_requests') { require_admin(); $db = DB::connect(); ok(['requests'=>$db->query("SELECT r.*, u.full_name AS patient_name FROM requests r JOIN users u ON r.patient_id=u.id ORDER BY r.created_at DESC LIMIT 50")->fetchAll()]); }

// --- UTILS ---
function haversine($lat1, $lon1, $lat2, $lon2) {
  $r = 6371;
  $dLat = deg2rad($lat2-$lat1); $dLon = deg2rad($lon2-$lon1);
  $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)**2;
  return $r * 2 * atan2(sqrt($a), sqrt(1-$a));
}
