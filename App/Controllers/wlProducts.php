<?php
include_once('App/Connections/db_con.php');
include_once('App/Controllers/auth.php');

class WlProducts
{

  //Get last added products from WL
  public function getWlProducts(string $token, string $nextpage, string $query)
  {

    $con = new con();
    $sql = "SELECT * FROM wl_credentials WHERE token = ?";
    $stmt = $con->conn()->prepare($sql);
    if ($stmt->execute([$token])) {
      $r = $stmt->fetch();

      $stmt = null;

      if ($nextpage == '' && $query == '') {
        $url = "https://services.leadconnectorhq.com/products/?locationId=" . $r['location'] . "&limit=15";
      } elseif ($nextpage != '' && $query == '') {
        $url = $nextpage;
      } elseif ($nextpage == '' && $query != '') {
        $url = "https://services.leadconnectorhq.com/products/?locationId=" . $r['location'] . "&limit=15&search=" . $query;
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

        if (isset($data->statusCode) && $data->statusCode == 401) {

          $refresh = new Auth();
  
          $newToken = $refresh->wlRefresh($token);
  
  
          return $this->getWlProducts($newToken, $nextpage, $query);
        }
      }
      return $data;
    } else {
      echo "This client doesn't exist";
    }
  }

  //Create new products to Wl from Ls
  public function addWlProducts(object $item, string $prodType, string $avaiable, string $token, string $locationId)
  {

    if ($avaiable == 'Store') {
      $avaiable = true;
    } else {
      $avaiable = false;
    };

    $amount = floatval($item->Prices->ItemPrice[0]->amount); // sets the string to integer

    $description = $item->description;

    //Creates the new product
    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://services.leadconnectorhq.com/products/",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        'name' => $item->description,
        'locationId' => $locationId,
        'description' => $item->description,
        'productType' => $prodType,
        'availableInStore' => $avaiable,
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


        return $this->addWlProducts($item, $prodType, $avaiable, $newToken, $locationId);
      }
      $id = $data->_id;
    }

    // Creates the price for the product above
    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://services.leadconnectorhq.com/products/" . $id . "/price",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        'name' => $description,
        'type' => 'one_time',
        'currency' => 'USD',
        'amount' => $amount,
        'locationId' => $locationId,
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
      $price = json_decode($response);

      $name = $price->name;

      echo "<div class='alert alert-success alert-dismissible fade show position-absolute w-100 text-center' role='alert'>".$name." added to your app<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }
  }
}
