<?php
// include_once 'App/Config/config.php';
include_once 'App/Connections/db_con.php';

class Auth
{
  public function chanato(){
    $con = new con();

    $sqlc = "SELECT * FROM ls_credentials WHERE accountId =?";
    $smtc = $con->conn()->prepare($sqlc);
    $smtc->execute(['115393']);
    $ui = $smtc->fetch();

    $smtc = null;

    if($ui){
      $_SESSION['ls_token'] = $ui['token'];
      $_SESSION['account_id'] = $ui['accountId'];
    }
  }

    //Validates the code came from Oauth WhiteLabel validation
  public function wlToken(string $code)
  {
    $curl = curl_init();

    curl_setopt_array($curl, [

      CURLOPT_URL => "https://services.leadconnectorhq.com/oauth/token",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "client_id=" . App\Config\config\wl_client_id . "&client_secret=" . App\Config\config\wl_client_secret . "&grant_type=authorization_code&code=" . $code,
      CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Content-Type: application/x-www-form-urlencoded"
      ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
    } else {

      $data = json_decode($response);

      if (isset($data->access_token)) {
        session_start();

        $userId = $data->userId;
        $location = $data->locationId;
        $token = $data->access_token;
        $refresh = $data->refresh_token;
        $status = 0;
        $date =  date("Y-m-d H:i:s");

        $check = new con();

        //Search in DB for a user that matches with the validated above
        $sqlc = "SELECT userId FROM wl_credentials WHERE userId =?";
        $smtc = $check->conn()->prepare($sqlc);
        $smtc->execute([$userId]);
        $ui = $smtc->fetch();

        $smtc = null;

        //Update the user data in the DB, if it already been created
        if (isset($ui) && $ui['userId'] == $userId) {
          $sqlu = "UPDATE wl_credentials SET token = ? , refresh_token = ?, timestamp = ? WHERE userId =?";
          $stmtu = $check->conn()->prepare($sqlu);

          if ($stmtu->execute([$token, $refresh, $date, $userId])) {
          } else {
            echo "Error update: " . $sqlu . "<br>" . $stmtu;
          }
        } else {

          //Create the user data in the DB, if it hasn't already been created
          $sql = "INSERT INTO wl_credentials (userId, location, token, refresh_token, status, timestamp) VALUES (?, ?, ?, ?, ?, ?)";

          $stmt = $check->conn()->prepare($sql);

          if ($stmt->execute([$userId, $location, $token, $refresh, $status, $date])) {
            $stmt = null;
          } else {
            echo "Error insert: " . $sql . "<br> $stmt";
          }
        }

        $_SESSION['wl_token'] = $data->access_token;
        $_SESSION['wl_location'] = $data->locationId;

      }
    }
  }

  //Validates the code came from Oauth Lightspeed validation
  public function lsToken(string $code, string $locationId)
  {
    $curl = curl_init();

    curl_setopt_array($curl, [

      CURLOPT_URL => "https://cloud.lightspeedapp.com/auth/oauth/token",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "client_id=" . App\Config\config\ls_client_id . "&client_secret=" . App\Config\config\ls_client_secret . "&grant_type=authorization_code&code=" . $code,
      CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Content-Type: application/x-www-form-urlencoded"
      ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
    } else {

      $data = json_decode($response);

      // echo $data->access_token;

      if (isset($data->access_token)) {
        session_start();

        $token = $data->access_token;
        $refresh = $data->refresh_token;
        $status = 0;
        $date =  date("Y-m-d H:i:s");

        $curl = curl_init();

        curl_setopt_array($curl, [

          CURLOPT_URL => "https://api.lightspeedapp.com/API/V3/Account.json",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Authorization: Bearer " . $token,
          ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
          $data = json_decode($response);

          if (isset($data->Account->accountID)) {
            $accountId = $data->Account->accountID;

            $check = new con();

            //Search in DB for a user that matches with the validated above
            $sqlc = "SELECT accountId FROM ls_credentials WHERE accountId =?";
            $smtc = $check->conn()->prepare($sqlc);
            $smtc->execute([$accountId]);
            $ui = $smtc->fetch();

            $smtc = null;

            if (isset($ui) && $ui['accountId'] == $accountId)  {
               //Update the user data in the DB, if it already been created
              $sqlu = "UPDATE ls_credentials SET token = ? , refresh_token = ?, locationId = ?, timestamp = ? WHERE accountId =?";
              $stmtu = $check->conn()->prepare($sqlu);

              if ($stmtu->execute([$token, $refresh, $locationId, $date, $accountId])) {
                echo "updated succefully";
                $stmtu = null;
              } else {
                echo "Error update: " . $sqlu . "<br>" . $stmtu;
              }
            } else {
              //Create the user data in the DB, if it hasn't already been created
              $sql = "INSERT INTO ls_credentials (accountId, token, refresh_token, status, locationId, timestamp) VALUES (?, ?, ?, ?, ?, ?)";

              $stmt = $check->conn()->prepare($sql);

              if ($stmt->execute([$accountId, $token, $refresh, $status, $locationId, $date])) {
                $stmt = null;
                echo "insert succecefuly";
              } else {
                echo "Error insert: " . $sql . "<br>";
              }
            }
            $_SESSION['account_id'] = $accountId;
            $_SESSION['ls_token'] = $token;
          }
        }
      }
    }
  }

   //Refresh White-Label token
  public function wlRefresh(string $Token)
  {
    $check = new con();
    $sqlc = "SELECT * FROM wl_credentials WHERE token = ?";
    $smtc = $check->conn()->prepare($sqlc);

    if ($smtc->execute([$Token])) {

      $ui = $smtc->fetch();
      $refreshToken = $ui['refresh_token'];

      $smtc = null;

      $curl = curl_init();

      curl_setopt_array($curl, [

        CURLOPT_URL => "https://services.leadconnectorhq.com/oauth/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "client_id=" . App\Config\config\wl_client_id . "&client_secret=" . App\Config\config\wl_client_secret . "&grant_type=refresh_token&refresh_token=" . $refreshToken,
        CURLOPT_HTTPHEADER => [
          "Accept: application/json",
          "Content-Type: application/x-www-form-urlencoded"
        ],
      ]);

      $response = curl_exec($curl);
      $err = curl_error($curl);

      curl_close($curl);

      if ($err) {
        echo "cURL Error #:" . $err;
      } else {

        $data = json_decode($response);

        var_dump($data);

        if (isset($data->access_token)) {
          $token = $data->access_token;
          $refresh = $data->refresh_token;
          $date = date("Y-m-d H:i:s");
          $userId = $data->locationId;

          $_SESSION['wl_token'] = $data->access_token;
          $_SESSION['wl_location'] = $data->locationId;

          $sqlu = "UPDATE wl_credentials SET token = ? , refresh_token = ?, timestamp = ? WHERE location =?";
          $stmtu = $check->conn()->prepare($sqlu);

          if ($stmtu->execute([$token, $refresh, $date, $userId])) {
            echo "updated succefully";
          } else {
            echo "Error update: " . $sqlu . "<br>" . $stmtu;
          }
        }
      }
    } else {
      echo 'Something is rong';
      header('location: /404');
    }
    return $token;
  }

  //Refresh Lightspeed token
  public function lsRefresh(string $token)
  {

    $check = new con();
    $sqlc = "SELECT * FROM ls_credentials WHERE token = ?";
    $smtc = $check->conn()->prepare($sqlc);

    if ($smtc->execute([$token])) {

      $ui = $smtc->fetch();
      $refreshToken = $ui['refresh_token'];

      $smtc = null;


      $curl = curl_init();

      curl_setopt_array($curl, [

        CURLOPT_URL => "https://cloud.lightspeedapp.com/auth/oauth/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "client_id=" . App\Config\config\ls_client_id . "&client_secret=" . App\Config\config\ls_client_secret . "&grant_type=refresh_token&refresh_token=" . $refreshToken,
        CURLOPT_HTTPHEADER => [
          "Accept: application/json",
          "Content-Type: application/x-www-form-urlencoded"
        ],
      ]);

      $response = curl_exec($curl);
      $err = curl_error($curl);

      curl_close($curl);

      if ($err) {
        echo "cURL Error #:" . $err;
      } else {

        $data = json_decode($response);

        if (isset($data->access_token)) {
          $newToken = $data->access_token;
          $refresh = $data->refresh_token;
          $date = date("Y-m-d H:i:s");
          $userId = $ui['accountId'];

          $_SESSION['ls_token'] = $newToken;

          $sqlu = "UPDATE ls_credentials SET token = ? , refresh_token = ?, timestamp = ? WHERE accountId =?";
          $stmtu = $check->conn()->prepare($sqlu);

          $stmtu->execute([$newToken, $refresh, $date, $userId]);

          $stmtu = null;
        }
      }
    } else {
      echo 'Something id rong';
      header('location: /404');
    }
    return $newToken;
  }
}
