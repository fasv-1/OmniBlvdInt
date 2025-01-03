<?php

// creates new custom field on WL
class CustomField
{
  public function createField(string $token, string $locationId, string $fieldName)
  {

    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://services.leadconnectorhq.com/locations/" . $locationId . "/customFields",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        'name' => $fieldName,
        'dataType' => 'TEXT',
        'placeholder' => $fieldName,
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


        return $this->createField($newToken, $locationId, $fieldName);
      }
    }
    return $data;
  }

   //Get all custom fields from WL
  public function getCustomFields(string $token, string $location)
  {

    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://services.leadconnectorhq.com/locations/" . $location . "/customFields",
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


        return $this->getCustomFields($newToken, $location);
      }
    }
    return $data;
  }
}
