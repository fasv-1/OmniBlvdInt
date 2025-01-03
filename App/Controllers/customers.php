<?php
include_once('App/Connections/db_con.php');
include_once('App/Controllers/auth.php');

class Costumer
{

  //Get customers from LS
  public function getCostumers(string $token, string $changePage, string $query)
  {
    $con = new con();
    $sql = "SELECT * FROM ls_credentials WHERE token =?";
    $stmt = $con->conn()->prepare($sql);
    if ($stmt->execute([$token])) {
      $r = $stmt->fetch();

      $stmt = null;

      $curl = curl_init();

      //separate the url's to diferente usage, nextpage, search query or simply get first page
      if ($changePage == '' && $query == '') {
        $url = "https://api.lightspeedapp.com/API/V3/Account/" . $r['accountId'] . "/Customer.json?limit=15&sort=-timeStamp&load_relations=all";
      } elseif ($changePage != '' && $query == '') {
        $url = $changePage;
      } elseif ($changePage == '' && $query != '') {
        $names = explode(" ", $query);
        $url = "https://api.lightspeedapp.com/API/V3/Account/" . $r['accountId'] . "/Customer.json?limit=15&load_relations=all&sort=-timeStamp&firstName=" . $names[0] . "&lastName=" . $names[1];
      }



      curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
          "Accept: application/json",
          "Authorization: Bearer " . $token,
          "Version: 2021-07-28"
        ],
      ]);

      $response = curl_exec($curl);
      $err = curl_error($curl);

      curl_close($curl);

      if ($err) {
        echo "cURL Error #:" . $err;
        // echo "You don't have any costumer with that name";
      } else {
        $data = json_decode($response);

        if (isset($data->httpCode) && $data->httpCode == '401') {
          $refresh = new Auth();

          $newToken = $refresh->lsRefresh($token);

          return $this->getCostumers($newToken, $changePage, $query);
        }
      }
    } else {
      echo "This client doesn't exist";
    }
    return $data;
  }
  
  //get single Customer
  public function getCustomer(string $customerId, string $token, string $accountId)
  {

    $curl = curl_init();

    $url = "https://api.lightspeedapp.com/API/V3/Account/" . $accountId . "/Customer/" . $customerId . ".json?load_relations=all";


    curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer " . $token,
        "Version: 2021-07-28"
      ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
    } else {
      $data = json_decode($response);

      if (isset($data->httpCode) && $data->httpCode == '401') {
        $refresh = new Auth();

        $newToken = $refresh->lsRefresh($token);

        return $this->getCustomer($customerId, $newToken, $accountId);
      }
    }
    return $data;
  }

  //Get the count of all customers
  public function countCostumers(string $token)
  {
    $con = new con();
    $sql = "SELECT * FROM ls_credentials WHERE token =?";
    $stmt = $con->conn()->prepare($sql);
    if ($stmt->execute([$token])) {
      $r = $stmt->fetch();

      $stmt = null;

      $curl = curl_init();

      $url = "https://api.lightspeedapp.com/API/V3/Account/" . $r['accountId'] . "/Customer.json?count=1";


      curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
          "Accept: application/json",
          "Authorization: Bearer " . $token,
          "Version: 2021-07-28"
        ],
      ]);

      $response = curl_exec($curl);
      $err = curl_error($curl);

      curl_close($curl);

      if ($err) {
        echo "cURL Error #:" . $err;
      } else {
        $data = json_decode($response);

        if (isset($data->httpCode) && $data->httpCode == '401') {
          $refresh = new Auth();

          $newToken = $refresh->lsRefresh($token);

          return $this->countCostumers($newToken);
        }

        $data = $data->{'@attributes'}->count;
      }
    } else {
      echo "This client doesn't exist";
    }
    return $data;
  }

  // creates a new customer in LS app
  public function addCostumers(object $contact, string $token, string $accountId)
  {
    // var_dump($contact->phone);
    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://api.lightspeedapp.com/API/V3/Account/" . $accountId . "/Customer.json",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        "firstName" => $contact->firstName,
        "lastName" => $contact->lastName,
        "title" => "",
        "company" => $contact->companyName,
        "companyRegistrationNumber" => "",
        "vatNumber" => "",
        "creditAccountID" => "0",
        "customerTypeID" => "1",
        "discountID" => "0",
        "taxCategoryID" => "0",
        "Contact" => [
          "custom" => "",
          "noEmail" => "false",
          "noPhone" => "false",
          "noMail" => "false",
          "Addresses" => [
            "ContactAddress" => [
              "address1" => $contact->address1,
              "address2" => "",
              "city" => $contact->city,
              "state" => $contact->state,
              "zip" => $contact->postalCode,
              "country" => $contact->country,
              "countryCode" => "",
              "stateCode" => ""
            ]
          ],
          "Phones" => [
            "ContactPhone" => [
              "number" => $contact->phone,
              "useType" => "Home"
            ]
          ],
          "Emails" => [
            "ContactEmail" => [
              "address" => $contact->email,
              "useType" => "Primary"
            ]
          ],
          "Websites" => $contact->website
        ]
      ]),
      CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer " . $token,
        "Content-Type: application/json",
      ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
    } else {
      $data = json_decode($response);

      if (isset($data->httpCode) && $data->httpCode == '401') {
        $refresh = new Auth();

        $newToken = $refresh->lsRefresh($token);

        return $this->addCostumers($contact, $newToken, $accountId);
      }

      return $data;
    }
  }
}
