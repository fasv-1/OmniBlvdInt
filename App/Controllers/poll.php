<?php
include_once('App/Connections/db_con.php');
include_once('App/Controllers/auth.php');
include_once('App/Controllers/customField.php');
require_once("App/Abstract/functionsLs.php");

class Poll
{
  //Set the values to start the pollSystem
  public function updatePoll(bool $switch, string $wlToken, string $accountId)
  {

    $con = new con();
    $sql = "SELECT * FROM wl_credentials WHERE token = ?";
    $stmt = $con->conn()->prepare($sql);
    if ($stmt->execute([$wlToken])) {
      $r = $stmt->fetch();
    } else {
      $error = $stmt->errorInfo();
      echo "Update Error: " . $error[2];
    }

    //If the user has never used the system, two custom fields are created in the WL app and the ids of these new fields are inserted into the DB, the user's status is also updated
    if ($r && $r['status'] == 0 && $r['customDate'] == null && $r['customValue'] == null && $switch === true) {
      $cCust = new CustomField();

      $getCustomField = $cCust->getCustomFields($wlToken, $r['location']);

      $customValue = '';
      $customDate = '';

      if (isset($getCustomField)) {
        if ($getCustomField->customFields != []) {
          foreach ($getCustomField->customFields as $customF) {

            if ($customF->name == 'Ls Sale Date') {
              $customDate = $customF->id;
            }

            if ($customF->name == 'Ls Sale Value') {
              $customValue = $customF->id;
            }
          }
        } elseif ($customDate == '' && $customValue == '') {
          $createDate = $cCust->createField($wlToken, $r['location'], 'Ls Sale Date');
          $customDate = $createDate->customField->id;

          $createValue = $cCust->createField($wlToken, $r['location'], 'Ls Sale Value');
          $customValue = $createValue->customField->id;
        }
      }

      $sqlu = "UPDATE wl_credentials SET status = ?, customDate = ?, customValue = ?  WHERE userId = ?";

      $stmtu = $con->conn()->prepare($sqlu);

      if ($stmtu->execute([1, $customDate, $customValue, $r['userId']])) {
        //Runs the enqueue function to add the task to the list
        return $this->enqueue('sales', $r['location'], $accountId);
      } else {
        $error = $stmtu->errorInfo();
        echo "Update Error: " . $error[2];
      }

      //If the user has already used the system, the user's status is updated and the task is added to queue
    } elseif ($r && $r['status'] == 0 && $r['customDate'] != null && $r['customValue'] != null && $switch === true) {
      $sqlu = "UPDATE wl_credentials SET status = ? WHERE location = ?";

      $stmtu = $con->conn()->prepare($sqlu);

      if ($stmtu->execute([1, $r['location']])) {
        return $this->enqueue('sales', $r['location'], $accountId);
      } else {
        $error = $stmtu->errorInfo();
        echo "Update Error: " . $error[2];
      }

      // If the switch is set to off, the status will be updated and the pollsystem will stop
    } elseif ($r && $r['status'] == 1 && $switch === false) {

      $sqlu = "UPDATE wl_credentials SET status = ? WHERE location = ?";

      $stmtu = $con->conn()->prepare($sqlu);

      if ($stmtu->execute([0, $r['location']])) {
        // return $this->cleanQueuelist();
      } else {
        $error = $stmtu->errorInfo();
        echo "Update Error: " . $error[2];
      }
    }
  }

  public function enqueue(string $id, string $location, string $account)
  {

    require_once 'vendor/autoload.php'; //Predis Autoload

    // Redis Instance
    $redis = new Predis\Client();

    // Get data send by the form
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

      // echo "Tarefa adicionada à fila com sucesso!";
    } else {
      echo "Por favor, forneça todos os dados necessários (location e account).";
    }
  }

  public function cleanQueuelist()
  {
    require 'vendor/autoload.php';

    $client = new Predis\Client();

    // Substitua 'nome_da_lista' pelo nome da sua lista
    $client->ltrim('polling_queue', 1, 0); // Fica uma lista vazia

    echo "Lista de tarefas esvaziada com sucesso!";
  }
}
