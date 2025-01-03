<?php
include_once('App/Connections/db_con.php');
include_once('App/Controllers/auth.php');

class Contacts
{

  //Get contacts from WhiteLabel App
  public function getContacts(string $token, array $nextpage, string $query)
  {
    // chechs the validated token
    $con = new con();
    $sql = "SELECT * FROM wl_credentials WHERE token = ?";
    $stmt = $con->conn()->prepare($sql);
    if ($stmt->execute([$token])) {
      $r = $stmt->fetch();

      $stmt = null;

      //separate the url's to diferente usage, nextpage, search query or simply get first page

      if ($query == "") {
        $filters = [];
      } else {
        $name = explode(' ', $query);

        $firstName = strtolower($name[0]);

        $filters = [
          [
            "field" => "firstNameLowerCase",
            "operator" => "eq",
            "value" => $firstName
          ],
        ];

        if (count($name) > 1) {
          $lastName = strtolower($name[1]);

          $filters = [
            [
              "group" => "AND",
              "filters" => [
                [
                  "field" => "firstNameLowerCase",
                  "operator" => "eq",
                  "value" => $firstName
                ],
                [
                  "field" => "lastNameLowerCase",
                  "operator" => "eq",
                  "value" => $lastName
                ]
              ]
            ]
          ];
        }
      }

      $curl = curl_init();

      curl_setopt_array($curl, [
        CURLOPT_URL => "https://services.leadconnectorhq.com/contacts/search",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
          "locationId" => $r['location'],
          "page" => 1,
          "pageLimit" => 15,
          "searchAfter" => $nextpage,
          "filters" => $filters,
          "sort" => [
            [
              "field" => "dateAdded",
              "direction" => "desc"
            ],
          ]

        ]),
        CURLOPT_HTTPHEADER => [
          "Accept: application/json",
          "Authorization: Bearer " . $token,
          "Content-Type: application/json",
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

        if (isset($data->statusCode) && $data->statusCode == 401) {

          $refresh = new Auth();

          $newToken = $refresh->wlRefresh($token);


          return $this->getContacts($newToken, $nextpage, $query);
        }

        return $data;
      }
      
    } else {
      echo "This client doesn't exist";
    }
  }

  //Get a single contact from WL
  public function getContact(string $contactId, string $token)
  {

    $url = "https://services.leadconnectorhq.com/contacts/" . $contactId;


    $curl = curl_init();

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

      if (isset($data->statusCode) && $data->statusCode == 401) {

        $refresh = new Auth();

        $newToken = $refresh->wlRefresh($token);


        return $this->getContact($contactId, $newToken);
      }
    }
    return $data;
  }

  //Create a contact based on the object send 
  public function addContacts(object $costumer, string $token, string $locationId)
  {

    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://services.leadconnectorhq.com/contacts/",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        'firstName' => $costumer->firstName,
        'lastName' => $costumer->lastName,
        'name' => $costumer->firstName . ' ' . $costumer->lastName,
        'email' => $costumer->Contact->Emails->ContactEmail->address,
        'locationId' => $locationId,
        'phone' => strval($costumer->Contact->Phones->ContactPhone->number),
        'address1' => $costumer->Contact->Addresses->ContactAddress->address1,
        'city' => $costumer->Contact->Addresses->ContactAddress->city,
        'state' => $costumer->Contact->Addresses->ContactAddress->state,
        'postalCode' => $costumer->Contact->Addresses->ContactAddress->zip,
        'website' => $costumer->Contact->Websites->url,
      ]),
      CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer " . $token,
        "Content-Type: application/json",
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

      //refresh token
      if (isset($data->statusCode) && $data->statusCode == 401) {

        $refresh = new Auth();

        $newToken = $refresh->wlRefresh($token);


        return $this->addContacts($costumer, $newToken, $locationId);
      }

      return $data;
    }
  }

  //create a new contact with a custom field (used on orders dynamic button)
  public function addCustomContact(object $costumer, array $custom, string $token, string $locationId)
  {
    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://services.leadconnectorhq.com/contacts/",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        'firstName' => $costumer->firstName,
        'lastName' => $costumer->lastName,
        'name' => $costumer->firstName . ' ' . $costumer->lastName,
        'email' => $costumer->Contact->Emails->ContactEmail->address,
        'locationId' => $locationId,
        'phone' => strval($costumer->Contact->Phones->ContactPhone->number),
        'address1' => $costumer->Contact->Addresses->ContactAddress->address1,
        'city' => $costumer->Contact->Addresses->ContactAddress->city,
        'state' => $costumer->Contact->Addresses->ContactAddress->state,
        'postalCode' => $costumer->Contact->Addresses->ContactAddress->zip,
        'website' => $costumer->Contact->Websites->url,
        'customFields' => $custom
      ]),
      CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer " . $token,
        "Content-Type: application/json",
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

      if (isset($data->statusCode) && $data->statusCode == 401) {

        $refresh = new Auth();

        $newToken = $refresh->wlRefresh($token);


        return $this->addCustomContact($costumer, $custom, $newToken, $locationId);
      }

      return $data;
    }
  }

  //if already exists just update de custom fields
  public function updateCustomContact(array $custom, string $contactId, string $token)
  {
    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://services.leadconnectorhq.com/contacts/" . $contactId,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "PUT",
      CURLOPT_POSTFIELDS => json_encode([
        'customFields' => $custom
      ]),
      CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer " . $token,
        "Content-Type: application/json",
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

      if (isset($data->statusCode) && $data->statusCode == 401) {

        $refresh = new Auth();

        $newToken = $refresh->wlRefresh($token);

        return $this->updateCustomContact($custom, $contactId, $newToken);
      }
    }
  }
}
