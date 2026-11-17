<?php
require_once __DIR__ . '/config.php';
class DB {
  private static $pdo = null;
  public static function connect() {
    if (self::$pdo) return self::$pdo;
    try {
      self::$pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false
        ]
      );
    } catch (Exception $e) {
      error_log($e->getMessage());
      die(json_encode(['success'=>false,'error'=>'DB unavailable']));
    }
    return self::$pdo;
  }

  public static function run($sql, $params = []) {
    $db = self::connect();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
  }

  public static function fetch($sql, $params = []) {
    return self::run($sql, $params)->fetch();
  }

  public static function fetchAll($sql, $params = []) {
    return self::run($sql, $params)->fetchAll();
  }
}
?>
