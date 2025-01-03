<?php
include 'App/Config/config.php';

//DB connection
class con
{
  private $host = App\Config\config\db_host;
  private $db = App\Config\config\db_name;
  private $user = App\Config\config\db_user;
  private $pass = App\Config\config\db_pass;
  
  function conn()
  {
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$charset";
    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
      $pdo = new PDO($dsn, $this->user, $this->pass, $options);
      return $pdo;
      $pdo = null;
    } catch (\PDOException $e) {
      echo "error: " . $e->getMessage();
      file_put_contents('App/connections/errorsLog/errors.log', 'Error: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
  }
}
