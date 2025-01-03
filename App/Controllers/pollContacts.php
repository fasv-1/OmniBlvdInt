<?php
include_once('App/Connections/db_con.php');
include_once('App/Controllers/auth.php');
include_once('App/Controllers/customField.php');
require_once("App/Abstract/functionsLs.php");

class PollContacts
{
  //Set the values to start or end the pollSystem for Contacts
  public function updateContactsPoll(bool $switch, string $wlToken, string $accountId)
  {

    //Db connection
    $con = new con();
    $sql = "SELECT * FROM wl_credentials WHERE token = ?";
    $stmt = $con->conn()->prepare($sql);

    if ($stmt->execute([$wlToken])) {
      $r = $stmt->fetch();

    } else {
      $error = $stmt->errorInfo();
      return ['error_msg' =>"DB connection Error: " . $error[2]];
    }

    //If the status_contacts is set to 0, and the button set to on, updates de DB 
    if ($r && $r['status_contacts'] == 0 && $switch === true) {

      $sqlu = "UPDATE wl_credentials SET status_contacts = ? WHERE location = ?";

      $stmtu = $con->conn()->prepare($sqlu);

      if ($stmtu->execute([1, $r['location']])) {
        // echo "<div class='alert alert-success alert-dismissible fade show position-absolute w-100 text-center' role='alert'> CRM contacts sync active <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
        //Runs the enqueue function to add the task to the queue list
        return $this->enqueueContacts($r['location'], $accountId, 'contacts');

      } else {
        $error = $stmtu->errorInfo();
        return ['error_msg' =>"Update Error: " . $error[2]];
      }

      //If the status_contacts is set to 1, and the button set to off, updates de DB 
    } elseif ($r && $r['status_contacts'] == 1 && $switch === false) {

      $sqlu = "UPDATE wl_credentials SET status_contacts = ? WHERE location = ?";

      $stmtu = $con->conn()->prepare($sqlu);

      if ($stmtu->execute([0, $r['location']])) {
        return ['msg' => 'CRM contacts automatic sync inactive'];

      } else {
        $error = $stmtu->errorInfo();
        return ['error_msg' =>"Update Error: " . $error[2]];

      }
    }
  }

  //Set the values to start or end the pollSystem for Costumers
  public function updateCustomersPoll(bool $switch, string $lsToken, string $locationId)
  {

    $con = new con();
    $sql = "SELECT * FROM ls_credentials WHERE token = ?";
    $stmt = $con->conn()->prepare($sql);

    if ($stmt->execute([$lsToken])) {
      $r = $stmt->fetch();

    } else {
      $error = $stmt->errorInfo();
      echo "Update Error: " . $error[2];

    }

    //If the status is set to 0, and the button set to on, updates de DB 
    if ($r && $r['status'] == 0 && $switch === true) {

      $sqlu = "UPDATE ls_credentials SET status = ? WHERE accountId = ?";

      $stmtu = $con->conn()->prepare($sqlu);

      if ($stmtu->execute([1, $r['accountId']])) {
        return ['msg' => 'Lightspeed customers automatic sync active'];

      } else {
        $error = $stmtu->errorInfo();
        return ['error_msg' =>"Update Error: " . $error[2]];

      }

      //If the status is set to 1, and the button set to off, updates de DB 
    } elseif ($r && $r['status'] == 1 && $switch === false) {

      $sqlu = "UPDATE ls_credentials SET status = ? WHERE accountId = ?";

      $stmtu = $con->conn()->prepare($sqlu);

      if ($stmtu->execute([0, $r['accountId']])) {
        return ['msg' => 'Lightspeed customers automatic sync inactive'];

      } else {
        $error = $stmtu->errorInfo();
        return ['error_msg' =>"Update Error: " . $error[2]];

      }
    }
  }

  public function enqueueContacts(string $location, string $account, string $id)
  {

    require_once 'vendor/autoload.php'; //Predis Autoload

    // Redis Instance
    $redis = new Predis\Client();

    // Get data send by previous function
    $location = $location ?? null;
    $account = $account ?? null;
    $id = $id ?? null;
    

    if ($id && $location && $account) {
      // Add the parameters to task 
      $task = [
        'id' => $id,
        'location' => $location,
        'account' => $account
      ];

      // Add the task to queue
      $redis->rpush('polling_queue', json_encode($task));

      // echo "Tarefa ".$id." adicionada à fila com sucesso!";
    } else {
      // echo "Por favor, forneça todos os dados necessários (location e account).";
    }
  }
}
