<?php
include_once('App/Connections/db_con.php');
include_once('App/Controllers/auth.php');

class Sales
{

   //Get sales all year round (recurtion needed because of several pages)
  public function getSalesYear(string $token, string $startDate, string $endDate)
  {
      $con = new con();
      $sql = "SELECT * FROM ls_credentials WHERE token = ?";
      $stmt = $con->conn()->prepare($sql);
      
      if ($stmt->execute([$token])) {
          $r = $stmt->fetch();

          $stmt = null;

          $urli = "https://api.lightspeedapp.com/API/V3/Account/".$r['accountId']."/Reports/Accounting/PaymentsByDay.json?startDate=".$startDate."&endDate=".$endDate;
  
          function runSales($url, $tk, $data = [])
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
                      "Authorization: Bearer " . $tk,
                      "Version: 2021-07-28"
                  ],
              ]);
  
              $response = curl_exec($curl);
              $err = curl_error($curl);
              curl_close($curl);
  
              if ($err) {
                  echo "cURL Error #:" . $err;
                  return $data; // Return data collected so far if there's an error
              } else {
                  $sales = json_decode($response);
  
                  if (isset($sales->Payments)) {
                      $data = array_merge($data, $sales->Payments);
                  }
  
                  $nextUrl = $sales->{'@attributes'}->next ?? null;
  
                  if ($nextUrl) {
                      return runSales($nextUrl, $tk, $data);
                  } else {
                      return $data;
                  }
              }
          }
      } else {
          echo "This client doesn't exist";
          return [];
      }
      
      return runSales($urli, $token);
      exit;
  }

  //Get last sales from Ls
  public function getLastSales(string $token, string $changePage, string $startDate, string $endDate, string $saleId)
  {
    $con = new con();
    $sql = "SELECT * FROM ls_credentials WHERE token =?";
    $stmt = $con->conn()->prepare($sql);
    if ($stmt->execute([$token])) {
      $r = $stmt->fetch();
      
      $stmt = null;
      
      $curl = curl_init();

      if ($changePage == '' && $startDate == '' && $saleId == '') {
        $url = "https://api.lightspeedapp.com/API/V3/Account/" . $r['accountId'] . "/Sale.json?limit=15&sort=-timeStamp&load_relations=all";
      
      } elseif($changePage != '' && $startDate == '' && $saleId == '') {
        $url = $changePage ;
      
      }elseif($changePage == '' && $startDate != '' && $endDate != '' && $saleId == ''){
        $url = "https://api.lightspeedapp.com/API/V3/Account/" . $r['accountId'] . "/Sale.json?limit=15&sort=-timeStamp&load_relations=all&timeStamp=%3E%3C," .$startDate. "," .$endDate;
      
      }elseif($changePage == '' && $startDate == '' && $endDate == '' && $saleId != ''){
        
        $url = "https://api.lightspeedapp.com/API/V3/Account/" . $r['accountId'] . "/Sale/" .$saleId.".json?load_relations=all";
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
      } else {
        $data = json_decode($response);

        if (isset($data->httpCode) && $data->httpCode == '401') {
          $refresh = new Auth();
  
          $newToken = $refresh->lsRefresh($token);
  
          return $this->getLastSales($newToken, $changePage, $startDate, $endDate, $saleId);
        }
        
      }
    } else {
      echo "This client doesn't exist";
    }
    return $data;
  }
}
