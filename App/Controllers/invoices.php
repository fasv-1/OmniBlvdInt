<?php
include_once('App/Connections/db_con.php');
include_once('App/Controllers/auth.php');
include_once('App/Controllers/contacts.php');

class Invoice
{

  //Get business information

  public function getAccountInfo(string $accountId, string $token)
  {

    $url = "https://services.leadconnectorhq.com/locations/" . $accountId;


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


        return $this->getAccountInfo($accountId, $newToken);
      }
    }
    return $data->location;
  }

  //Create a new invoice on WL based on the sale came from Ls
  public function createInvoice(string $locationId, string $WlToken, object $sale, object $form)
  {

    $cont = new Contacts();
    $contact = $cont->addContacts($sale->Customer, $WlToken, $locationId);

    $info = $this->getAccountInfo($locationId, $WlToken);

    if (isset($contact->contact)) {
      $contactId = $contact->contact->id;
    } else {
      $contactId = $contact->meta->contactId;
    }

    $items = [];
    $saleLine = $sale->SaleLines->SaleLine;


    if (is_array($saleLine) == true) {
      foreach ($saleLine as $line) {
        if (isset($line->Item)) {
          $item = [
            'name' => $line->Item->description,
            // 'description' => $line->Item->description,
            'currency' => 'USD',
            'amount' => $line->unitPrice,
            'qty' => $line->unitQuantity,
            // 'taxes'=>[
            //   [
            //     '_id' => $line->taxClassID,
            //     'name' => $line->TaxClass->name,
            //     'rate' => strval(($line->tax1Rate)*100),
            //     'calculation' => 'exclusive',
            //     'description' => '',
            //     'taxId' => $line->taxClassID
            //   ]
            // ]
          ];
          array_push($items, $item);
        } else {
          $item = [
            'name' => $line->TaxClass->name,
            'currency' => 'USD',
            'amount' => $line->unitPrice,
            'qty' => $line->unitQuantity,
            // 'taxes'=>[
            //   [
            //     '_id' => $line->taxClassID,
            //     'name' => $line->TaxClass->name,
            //     'rate' => strval(($line->tax1Rate)*100),
            //     'calculation' => 'exclusive',
            //     'description' => '',
            //     'taxId' => $line->taxClassID
            //   ]
            // ]
          ];
          array_push($items, $item);
        }
      }
    } else {
      if (isset($saleLine->Item)) {
        $item = [
          'name' => $saleLine->Item->description,
          // 'description' => $saleLine->Item->description,
          'currency' => 'USD',
          'amount' => $saleLine->unitPrice,
          'qty' => $saleLine->unitQuantity,
          // 'taxes'=>[
          //     [
          //       '_id' => 'string',
          //       'name' => $saleLine->TaxClass->name,
          //       'rate' => strval(($saleLine->tax1Rate)*100),
          //       'calculation' => 'exclusive',
          //       'description' => '',
          //       'taxId' => $saleLine->taxClassID
          //     ]
          //   ]
        ];
      } else {
        $item = [
          'name' => $saleLine->TaxClass->name,
          'currency' => 'USD',
          'amount' => $saleLine->unitPrice,
          'qty' => $saleLine->unitQuantity,
          // 'taxes'=>[
          //     [
          //       '_id' => $saleLine->taxClassID,
          //       'name' => $saleLine->TaxClass->name,
          //       'rate' => strval(($saleLine->tax1Rate)*100),
          //       'calculation' => 'exclusive',
          //       'description' => '',
          //       'taxId' => $saleLine->taxClassID
          //     ]
          //   ]
        ];
      }
      array_push($items, $item);
    }

    $issueDate = explode('T', $sale->completeTime);

    // var_dump($items);

    $contactEmail = $sale->Customer->Contact->Emails->ContactEmail;
    $contactPhone = $sale->Customer->Contact->Phones->ContactPhone;

    if (is_array($contactEmail)) {
      $contEmail = $sale->Customer->Contact->Emails->ContactEmail[0]->address;
    } else {
      if (!isset($contactEmail)) {
        $contEmail = $form->sendEmail;
      } else {
        $contEmail = $sale->Customer->Contact->Emails->ContactEmail->address;
      }
    }

    if (is_array($contactPhone)) {
      $contPhone = $sale->Customer->Contact->Phones->ContactPhone[0]->number;
    } else {
      if (!isset($contactPhone)) {
        $contPhone = $form->sendPhone;
      } else {
        $contPhone = $sale->Customer->Contact->Phone->ContactPhone->address;
      }
    }

    if ($sale->discountPercent == '') {
      $discount = 0;
    } else {
      $discount = intval($sale->discountPercent);
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://services.leadconnectorhq.com/invoices/",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        'altId' => $locationId,
        'altType' => 'location',
        'name' => $form->invoiceName,
        'businessDetails' => [
          'logoUrl' => $info->business->logoUrl,
          'name' => $info->business->name,
          'phoneNo' => $info->phone,
          'address' => [
            'addressLine1' => $info->business->address,
            'addressLine2' => '',
            'city' => $info->business->city,
            'state' => $info->business->state,
            'countryCode' => $info->business->country,
            'postalCode' => $info->business->postalCode,
          ],
          'website' => $info->business->website,
        ],
        'currency' => 'USD',
        'items' => $items,
        'discount' => [
          'value' => $discount,
          'type' => 'percentage'
        ],
        'title' => 'INVOICE',
        'contactDetails' => [
          'id' => $contactId,
          'name' => $sale->Customer->firstName . ' ' . $sale->Customer->lastName,
          'phoneNo' => $contPhone,
          'email' => $contEmail,
          'companyName' => $sale->Customer->company,
          'address' => [
            'addressLine1' => $sale->Customer->Contact->Addresses->ContactAddress->address1,
            'addressLine2' => $sale->Customer->Contact->Addresses->ContactAddress->address2,
            'city' => $sale->Customer->Contact->Addresses->ContactAddress->city,
            'state' => $sale->Customer->Contact->Addresses->ContactAddress->state,
            'countryCode' => $sale->Customer->Contact->Addresses->ContactAddress->country,
            'postalCode' => $sale->Customer->Contact->Addresses->ContactAddress->zip,
          ],
        ],
        'invoiceNumber' => '',
        'issueDate' => $issueDate[0],
        'dueDate' => $form->dueDate,
        'sentTo' => [
          'email' => [
            $form->sendEmail
          ],
          'emailCc' => [],
          'emailBcc' => [],
          'phoneNo' => [
            $form->sendPhone
          ]
        ],
        'liveMode' => $form->liveMode,
      ]),
      CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer " . $WlToken,
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
      $resp = json_decode($response);

      if (isset($data->statusCode) && $resp->statusCode == 401) {

        $refresh = new Auth();

        $newToken = $refresh->wlRefresh($WlToken);


        return $this->createInvoice($locationId, $newToken, $sale, $form);
      }
    }

    return $resp;
  }
}
