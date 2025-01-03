<?php
// Required files
require_once('App/Connections/db_con.php');
require_once('App/Controllers/auth.php');

// Read raw POST data
$input = file_get_contents('php://input');

// General funtion for log messages with time stamp
function logMessage(string $message): void
{
  file_put_contents('webhook.log', date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}

// Decode JSON if applicable
$data = json_decode($input, true);

// Check if decoding was successful
if ($data === null) {
  file_put_contents('webhook.log', "JSON decode error: " . json_last_error_msg() . PHP_EOL, FILE_APPEND);
  http_response_code(400); // Bad Request
  echo "Invalid payload";
  exit;
} else {
  //checks if the user haves the app installed and if the data recived is realted with contact create
  if ($data['appId'] == '670d2e6c7db6e43eb640f9c3' && $data['type'] == 'ContactCreate') {

    $con = new con();

    $locationId = $data['locationId'];

    // Get info for DB based ond the location recived 
    $stmt = $con->conn()->prepare("SELECT * FROM ls_credentials WHERE locationId = ?");
    $stmt->execute([$locationId]);
    $lsCredentials = $stmt->fetch();
    $stmt = null;

    // Validates the info from DB
    if (!$lsCredentials) {
      throw new Exception("Credentials not found for location '$locationId'.");
      logMessage("Credentials not found for location '$locationId'.");
      exit;
    }

    // Checks the status of user, if is 0, logs a messages as "inative"
    if ($lsCredentials['status'] != 1) {
      logMessage("Status inative for location '$locationId'.");

      // If the status is ative, sends a request to create a new customer on Ls
    } else {

      function Customer(array $data, string $token, string $accountId)
      {
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
            "firstName" => $data['firstName'],
            "lastName" => $data['lastName'],
            "title" => "",
            "company" => "",
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
                  "address1" => "",
                  "address2" => "",
                  "city" => "",
                  "state" => "",
                  "zip" => "",
                  "country" => $data['country'],
                  "countryCode" => "",
                  "stateCode" => ""
                ]
              ],
              "Phones" => [
                "ContactPhone" => [
                  "number" => $data['phone'],
                  "useType" => "Home"
                ]
              ],
              "Emails" => [
                "ContactEmail" => [
                  "address" => $data['email'],
                  "useType" => "Primary"
                ]
              ],
              "Websites" => ""
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
          logMessage("cURL Error #:" . $err);
        } else {
          $data = json_decode($response);

          if (isset($data->httpCode) && $data->httpCode == '401') {
            $refresh = new Auth();

            $newToken = $refresh->lsRefresh($token);

            return Customer($data, $newToken, $accountId);
          }

          if ($data->statusCode == 400) {
            logMessage("Costumer not added for account $accountId\n" .print_r($data));
          
          } else {
            logMessage("Costumer add successfuly for account " . $accountId);

          }
        }
      }

      Customer($data, $lsCredentials['token'], $lsCredentials['accountId']);
    }
  }
  // logMessage("Passei aqui.");
}


// Respond with success and log data
// file_put_contents('webhook.log', print_r($data, true) . PHP_EOL, FILE_APPEND);
http_response_code(200);
echo "Webhook received successfully!";
