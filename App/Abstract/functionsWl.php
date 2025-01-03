<?php
include_once('App/Controllers/auth.php');

// Abstratct funtion to get single data from WhiteLabel App
class AbstractWl
{
  public function getSingleWlData(string $token, string $url)
  {

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

      //Refreshes de token from WLApp if it expires
      if (isset($data->statusCode) && $data->statusCode == 401) {

        $refresh = new Auth();

        $newToken = $refresh->wlRefresh($token);

        return $this->getSingleWlData($newToken, $url);
      }
    }
    return $data;
  }
}
