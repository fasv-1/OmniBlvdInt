<?php

class lsProducts
{

  //Get all Ls products
  public function getLsProducts(string $token, string $nextpage, string $query)
  {
    $con = new con();
    $sql = "SELECT * FROM ls_credentials WHERE token =?";
    $stmt = $con->conn()->prepare($sql);
    if ($stmt->execute([$token])) {
      $r = $stmt->fetch();
      $stmt = null;

      if ($nextpage == '' && $query == '') {
        $url = "https://api.lightspeedapp.com/API/V3/Account/" . $r['accountId'] . "/Item.json?limit=15&sort=-timeStamp&load_relations=all";
      } elseif ($nextpage != '' && $query == '') {
        $url = $nextpage;
      } elseif ($nextpage == '' && $query != '') {
        $url = "https://api.lightspeedapp.com/API/V3/Account/" . $r['accountId'] . "/Item.json?sort=-timeStamp&limit=15&load_relations=all&description=" . $query;
      }

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

        if (isset($data->httpCode) && $data->httpCode == '401') {
          $refresh = new Auth();

          $newToken = $refresh->lsRefresh($token);

          return $this->getLsProducts($newToken, $nextpage, $query);
        }
      }
    } else {
      echo "This client doesn't exist";
    }
    return $data;
  }

    //Get Ls product by id
  public function getProductById(string $itemId, string $token, string $accountId)
  {
    $url = "https://api.lightspeedapp.com/API/V3/Account/" . $accountId . "/Item/" . $itemId . ".json";


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


      if (isset($data->httpCode) && $data->httpCode == '401') {
        $refresh = new Auth();

        $newToken = $refresh->lsRefresh($token);

        return $this->getProductById($itemId, $newToken, $accountId);
      }
    }
    return $data;
  }
}
