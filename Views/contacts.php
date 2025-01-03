<?php
require_once("Includes/head.php");
require_once("App/Controllers/customers.php");
require_once("App/Controllers/contacts.php");
require_once("App/Controllers/pollContacts.php");
require_once("Includes/sidebar.php");
include_once('App/Connections/db_con.php');

session_start();
error_reporting(0);

//----------------------------------------------------------------
//---------------- Validation and redirection -------------------- 
//----------------------------------------------------------------
if (!isset($_SESSION['wl_token']) &&  !isset($_SESSION['wl_location'])) {
  header("location: /landing");
} elseif (!isset($_SESSION['ls_token']) && !isset($_SESSION['account_id'])) {
  header("location: /auth");
}

//----------------------------------------------------------------
//--------------------- Indetification variables ----------------- 
//----------------------------------------------------------------
$sess = $_SESSION['wl_token'];
$locationId = $_SESSION['wl_location'];
$cont = new Contacts();

$lsToken = $_SESSION['ls_token'];
$accountId = $_SESSION['account_id'];

$cost = new Costumer();

$con = new con();

$goToTables = false;

//---------------------------------------------------------------------------
//------------------------------ Ls Costumers ------------------------------
//---------------------------------------------------------------------------

$sql = "SELECT * FROM ls_credentials WHERE token = ?";
$stmt = $con->conn()->prepare($sql);

//set the value off the switch button in the beginning
if ($stmt->execute([$lsToken])) {
  $r = $stmt->fetch();

  if ($r['status'] == 0) {
    $switchLValue = false;
  } else {
    $switchLValue = true;
  }
}

$newPoll = new PollContacts();

//swithes the value off the switch button and starts or ends the polling system
if (isset($_GET['switchCost'])) {
  $goToTables = false;
  if ($_GET['switchCost'] == 'onSwitch') {
    $switchLValue = true;

    $lStatus = $newPoll->updateCustomersPoll($switchLValue, $lsToken, $locationId);


  } elseif ($_GET['switchCost'] == 'offSwitch') {
    $switchLValue = false;

    $lStatus = $newPoll->updateCustomersPoll($switchLValue, $lsToken, $locationId);

  }
};

//-------------------------------next page-----------------------------------
if (isset($_POST['nextLs'])) {
  $goToTables = true;

  $_SESSION['LsPage'] = $_POST['nextLs'];
  $_SESSION['ls_count'] += 1;
  $reqCost = $cost->getCostumers($lsToken, $_POST['nextLs'], '');
  $costumers = $reqCost->Customer;

  if (isset($_SESSION['ls_count'])) {
    $nContLs = ($_SESSION['ls_count'] + 1);
  } else {
    $nContLs = 1;
  }
  //-------------------------------preview page-----------------------------------
} elseif (isset($_POST['prevLs'])) {
  $goToTables = true;

  $_SESSION['LsPage'] = $_POST['prevLs'];
  $_SESSION['ls_count'] -= 1;
  $reqCost = $cost->getCostumers($lsToken, $_POST['prevLs'], '');
  $costumers = $reqCost->Customer;

  if (count($costumers) == 15 && isset($_SESSION['ls_count'])) {
    if ($_SESSION['ls_count'] == 0) {
      $nContLs = 1;
    } else {
      $nContLs = ($_SESSION['ls_count'] + 1);
    }
  }
  //-------------------------------start page-----------------------------------
} elseif (!isset($_POST['nextLs']) && !isset($_POST['prevLs']) && !isset($_POST['nameSearchLs']) && !isset($_POST['AddCostumers'])) {
  $reqCost = $cost->getCostumers($lsToken, '', '');
  $costumers = $reqCost->Customer;

  $nContLs = 1;

  if (is_array($costumers) == false) {
    $itm = $costumers;

    $costumers = [];

    array_push($costumers, $itm);
  };

  unset($_SESSION['LsPage']);
  unset($_SESSION['ls_count']);
}

//-------------------------------search input-----------------------------------
if (isset($_POST['nameSearchLs'])) {
  $goToTables = true;
  $reqCost = $cost->getCostumers($lsToken, '', $_POST['nameSearchLs']);
  $costumers = $reqCost->Customer;

  if (is_array($costumers) == false) {
    $itm = $costumers;

    $costumers = [];

    array_push($costumers, $itm);
  };

  $nContLs = 1;
}

//-------------------------------Create contacts -----------------------------------
if (isset($_POST['AddCostumers']) && isset($_POST['pageNl'])  && isset($_POST['costumerId'])) {
  $goToTables = true;

  if (isset($_SESSION['LsPage'])) {
    $reqCost = $cost->getCostumers($lsToken, $_SESSION['LsPage'], '');
  } else {
    $reqCost = $cost->getCostumers($lsToken, '', '');
  }
  $costumers = $reqCost->Customer;

  $costS = json_decode($_POST['costumerId']);

  foreach ($costS as $custmr) {
    $reqCs = $cost->getCustomer($custmr, $lsToken, $accountId);
    $costumer = $reqCs->Customer;

    $newContact = $cont->addContacts($costumer, $sess, $locationId);

    //if already exists displays a danger message, if don't displays a success message
      if (isset($newContact->statusCode) && $newContact->statusCode == 400) {
        echo "<div class='alert alert-danger alert-dismissible fade show position-absolute w-100 text-center' role='alert'>" . $newContact->message . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
      } else {
        $contact = $newContact->contact;
        echo "<div class='alert alert-success alert-dismissible fade show position-absolute w-100 text-center' role='alert'>" . $contact->firstName . " " . $contact->lastName . " added to your contacts list<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
      }
  }

  $nContLs = $_POST['pageNl'];
  $_SESSION['ls_count'] = $_POST['pageNl'] - 1;
}


//---------------------------------------------------------------------------
//-----------------------------General functions ----------------------------
//---------------------------------------------------------------------------

function endKey($array)
{
  end($array);
  return key($array);
}


//---------------------------------------------------------------------------
//--------------------------------- WL Contacts -----------------------------
//---------------------------------------------------------------------------

$sql = "SELECT * FROM wl_credentials WHERE token = ?";
$stmt = $con->conn()->prepare($sql);

// Set the value off the switch button in the beginning
if ($stmt->execute([$sess])) {
  $r = $stmt->fetch();

  if ($r['status_contacts'] == 0) {
    $switchWValue = false;
  } else {
    $switchWValue = true;
  }
}

// Swithes the value on/off of the switch button and starts/ends the polling for contacts
if (isset($_GET['switchCont'])) {
  $goToTables = false;
  if ($_GET['switchCont'] == 'onSwitch') {
    $switchWValue = true;

    $newPoll->updateContactsPoll($switchWValue, $sess, $accountId);
  } elseif ($_GET['switchCont'] == 'offSwitch') {
    $switchWValue = false;

    $wStatus = $newPoll->updateContactsPoll($switchWValue, $sess, $accountId);

  }
};

//-------------------------------Search page-----------------------------------
if (isset($_POST['nameSearchWl'])) {
  unset($_SESSION['pagesWl']);

  $goToTables = true;

  // Saves the searched content for the next pages
  $_SESSION['firstSearch'] = $_POST['nameSearchWl'];

  $reqs = $cont->getContacts($sess, [], $_POST['nameSearchWl']);

  $contacts = $reqs->contacts;

  if ($contacts) {
    $lastContact = end($contacts);
    $nextpage = $lastContact->searchAfter;
  }
  
  // Forces an array to avoid errors
  if (is_array($contacts) == false) {
    $itm = $contacts;

    $contacts = [];

    array_push($contacts, $itm);
  };

  $nCont = 1;
}

//-------------------------------Start page-----------------------------------
if (!isset($_POST['next']) && !isset($_POST['preview']) && !isset($_POST['nameSearchWl']) && !isset($_POST['AddContacts'])) {
  
  // Unset all variables needed for navigation
  unset($_SESSION['pagesWl']);
  unset($_SESSION['firstSearch']);
  unset($_SESSION['WlPage']);

  $reqs = $cont->getContacts($sess, [], '');

  $contacts = $reqs->contacts;

  if ($contacts) {
    $lastContact = end($contacts);
    $nextpage = $lastContact->searchAfter;
  }

  if (is_array($contacts) === false) {
    $itm = $contacts;

    $contacts = [];

    array_push($contacts, $itm);
  };

  $nCont = 1;
}

//-------------------------------next page (first) -----------------------------------
if (isset($_POST['next']) && !isset($_SESSION['pagesWl'])) {
  $goToTables = true;

  // Declares the session variable as an array
  $_SESSION['pagesWl'] = array();
  $_SESSION['WlPage'] = $_POST['next'];

  // Gets the searched value in case of need
  if (isset($_SESSION['firstSearch'])) {
    $firstSearch = $_SESSION['firstSearch'];
  } else {
    $firstSearch = '';
  }

  // Stores in a array session the values to change page (for pagination an preview) 
  array_push($_SESSION['pagesWl'], $_POST['next']);

  // Decode the info send on request
  $next = json_decode($_POST['next'], true);

  $reqs = $cont->getContacts($sess, $next, $firstSearch);

  $contacts = $reqs->contacts;

  // Gets the last value of the array of contacts to extract the info for change page
  if ($contacts) {
    $lastContact = end($contacts);

    $nextpage = $lastContact->searchAfter;
  }

  // Set the number of page based on the amount of values on array ( +1 because in the first page, the array doesn't exists)
  if (isset($_SESSION['pagesWl'])) {
    $nCont = (count($_SESSION['pagesWl']) + 1);
  } else {
    $nCont = 1;
  }

  //-------------------------------next page-----------------------------------
} elseif (isset($_POST['next']) && isset($_SESSION['pagesWl'])) {
  $goToTables = true;

  $_SESSION['WlPage'] = $_POST['next'];

  if (isset($_SESSION['firstSearch'])) {
    $firstSearch = $_SESSION['firstSearch'];
  } else {
    $firstSearch = '';
  }

  array_push($_SESSION['pagesWl'], $_POST['next']);

  $next = json_decode($_POST['next'], true);

  $reqs = $cont->getContacts($sess, $next, $firstSearch);

  $contacts = $reqs->contacts;

  if ($contacts) {
    $lastContact = end($contacts);
    $nextpage = $lastContact->searchAfter;
  }

  $nCont = (count($_SESSION['pagesWl']) + 1);
}

//-------------------------------previous page-----------------------------------
if (isset($_POST['preview']) && isset($_SESSION['pagesWl']) && count($_SESSION['pagesWl']) > 1) {
  $goToTables = true;

  // Gets the key of the last element in array -1 because the first page will never be on the array 
  $keyPrev = endKey($_SESSION['pagesWl']) - 1;

  if (isset($_SESSION['firstSearch'])) {
    $firstSearch = $_SESSION['firstSearch'];
  } else {
    $firstSearch = '';
  }

  // Gets the info to search the page based on the key calculated above.
  $previewPage = $_SESSION['pagesWl'][$keyPrev];

  $_SESSION['WlPage'] = $previewPage;

  $preview = json_decode($previewPage, true);

  $reqs = $cont->getContacts($sess, $preview, $firstSearch);

  $contacts = $reqs->contacts;

  if ($contacts) {
    $lastContact = end($contacts);
    $nextpage = $lastContact->searchAfter;
  }

  $nCont = count($_SESSION['pagesWl']);

  //removes the element from the array used to get this page
  array_splice($_SESSION['pagesWl'], endkey($_SESSION['pagesWl']), 1);

  //------------------------------- previous last page -----------------------------------
} elseif (isset($_POST['preview']) && isset($_SESSION['pagesWl']) && count($_SESSION['pagesWl']) == 1) {
  $goToTables = true;

  if (isset($_SESSION['firstSearch'])) {
    $firstSearch = $_SESSION['firstSearch'];
  } else {
    $firstSearch = '';
  }

  $_SESSION['WlPage'] = $firstSearch;

  $reqs = $cont->getContacts($sess, [], $firstSearch);

  $contacts = $reqs->contacts;

  if ($contacts) {
    $lastContact = end($contacts);
    $nextpage = $lastContact->searchAfter;
  }

  $nCont = 1;

  unset($_SESSION['pagesWl']);
}

//-------------------------------Create new costumers-----------------------------------
if (isset($_POST['AddContacts']) && isset($_POST['pagesNw'])  && isset($_POST['contactId'])) {
  $goToTables = false;
  if (isset($_SESSION['firstSearch'])) {
    $firstSearch = $_SESSION['firstSearch'];
  } else {
    $firstSearch = '';
  }
  
  if (isset($_SESSION['WlPage'])) {
    $currentPage = json_decode($_SESSION['WlPage'],true);
    $reqs = $cont->getContacts($sess, $currentPage, $firstSearch );
  } else {
    $reqs = $cont->getContacts($sess, [], $firstSearch );
  }
  $contacts = $reqs->contacts;

  if ($contacts) {
    $lastContact = end($contacts);
    $nextpage = $lastContact->searchAfter;
  }
  $contS = json_decode($_POST['contactId']);

  // Search a contact for each id and try to creat new costumers
  foreach ($contS as $ctc) {

    $reqWl = $cont->getContact($ctc, $sess);

    $contact = $reqWl->contact;

    $newCostumer = $cost->addCostumers($contact, $lsToken, $accountId);

    $costumer = $newCostumer->Customer;

    if ($newCostumer->statusCode == 400) {
      echo "<div class='alert alert-danger alert-dismissible fade show position-absolute w-100 text-center' role='alert'>" . $newCostumer->message . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    } else {
      echo "<div class='alert alert-success alert-dismissible fade show position-absolute w-100 text-center' role='alert'>" . $costumer->firstName . " " . $costumer->lastName . " added to your costumers list<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }
  }

  $nCont = $_POST['pagesNw'];
}

//-------------------------------------------------------------------------
//--------------- End pagination and navigation ---------------------------
//-------------------------------------------------------------------------

?>
<div class="d-flex">
  <div class="p-3" style="width:350px; height:100vh"></div>
  <div class="container-fluid d-flex flex-column align-items-center mt-5">
    <div class="row w-100 text-center">
      <h3>Contacts | Customers</h3>
    </div>

    <div class="row w-100 mt-3 text-center">
      <h6 class="text-danger">Important Note:</h6>
      <h6 class="w-75 mx-auto">Due to API limitations, an automatic initial sync is not possible. Only contacts added or updated after enabling this option will sync automatically. To sync existing contacts, you'll need to manually export them from your Lightspeed app and import them into your CRM.</h6>
      <h6>For a step-by-step guide, watch this video: <a class="link-dark link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover" href="">How to Export and Import Contacts</a></h6>
    </div>

    <!------------------------------------------------------------------------------------------------------------>
    <!---------------------------------------- Automatic sync buttons -------------------------------------------->
    <!------------------------------------------------------------------------------------------------------------>

    <div class="row w-100 d-flex align-items-center justify-content-between mt-3 mb-5">
      <div class="col-5 d-flex justify-content-center align-items-center pt-2 pb-2 rounded shadow-sm " style="background-color:#e6e6e6">
        <div class="bg-white p-3 rounded">
          <div class="row">
            <h3>CRM Automatic Contacts Sync </h3>
          </div>
          <div class="row m-1">
            <div class="form-check form-switch ">
              <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckContacts" style="width:70px; height:40px">
            </div>
          </div>
          <div class="row text-muted pt-1">
            <p class="text-muted">When you switch this option on, the new costumers added to your Lightspeed app will automaticly be created on you CRM. </p>
          </div>
        </div>
      </div>
      <div class="col-5 d-flex justify-content-center align-items-center pt-2 pb-2 rounded shadow-sm" style="background-color:#e6e6e6">
        <div class="bg-white p-3 rounded">
          <div class="row">
            <h3>Lightspeed Automatic Contacts Sync </h3>
          </div>
          <div class="row m-1">
            <div class="form-check form-switch ">
              <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckCustomers" style="width:70px; height:40px">
            </div>
          </div>
          <div class="row text-muted pt-1">
            <p class="text-muted">When you switch this option on, the new contacts added to your CRM will automaticly be created on your Lightspeed app. </p>
          </div>
        </div>
      </div>
    </div>

    <div class="row w-100 mt-2 mb-5">

      <!------------------------------------------------------------------------------------------------------------>
      <!------------------------------------------- Clients Table -------------------------------------------------->
      <!------------------------------------------------------------------------------------------------------------>

      <div class="col-5 rounded p-2 shadow-sm bg-body-tertiary" style="border:1px solid #e6e6e6">
        <div id="CenterOnChange" class="d-flex justify-content-around align-items-center mb-2 mt-2">
          <div>
            <h6 class="">CRM Contacts</h6>
          </div>
          <div class="d-flex align-items-center justify-contente-center">
            <form method="post"><input class="rounded border-light" type="text" class="form-control" placeholder="Search a contact" name="nameSearchWl"><button type="submit" class="btn btn-sm mb-2 ms-1"><img src='../Assets/search.png' alt='Search'></button></form>
          </div>
        </div>
        <table class="table table-striped table-hover caption-top align-middle text-center">
          <thead>
            <tr>
              <th scope="col"></th>
              <th scope="col">Name</th>
              <th scope="col">Date</th>
              <th scope="col"></th>
            </tr>
          </thead>
          <tbody id="contactTable">
            <?php
            if ($contacts != [NULL] || $contacts != []) {
              foreach ($contacts as $key => $conta) {
                $date = explode('T', $conta->dateAdded);
            ?>
                <tr>
                  <td><input type="checkbox" name="selectWContact" id="selectWContact" onchange="SelectedWlC('<?php echo $conta->id ?>')" value="<?php echo $conta->id ?>"></td>
                  <td>
                    <?php if ($conta->firstNameLowerCase == null && $conta->lastNameLowerCase == null) {
                      echo $conta->phone;
                    } else {
                      echo $conta->firstNameLowerCase . ' ' . $conta->lastNameLowerCase;
                    } ?>
                  </td>
                  <td><?php echo $date[0]; ?></td>
                  <td>
                    <form method="post" style="margin:0;">
                      <input type="hidden" name="contactId" value='<?php echo json_encode([$conta->id]) ?>'>
                      <input type="hidden" name="pagesNw" value="<?php echo $nCont ?>">
                      <button type="submit" name="AddContacts" style="border:none; background:none">
                        <img src='../Assets/arrow-left.png' alt='Arrow' style='transform:rotate(180deg)'>
                      </button>
                    </form>
                  </td>
                </tr>

              <?php
              }
            } else {
              ?>
              <tr>
                <td colspan="4">There are no contacts</td>
              </tr>
            <?php
            };
            ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <form method="post" style="margin:0;">
                      <input type="hidden" id="preview" name="preview" value="preview">
                      <button type="submit" class="btn btn-light" <?php if (!isset($_SESSION['pagesWl'])) {
                                                                    echo "disabled";
                                                                  } ?>>
                        < preview
                          </button>
                    </form>
                  </div>
                  <div><?php echo 'Page ' . $nCont ?></div>
                  <div>
                    <form method="post" style="margin:0;">
                      <input type="hidden" id="nextPageData" name="next">
                      <button type="submit" class="btn btn-light" <?php if ($contacts == []) {
                                                                    echo "disabled";
                                                                  } ?>>
                        next >
                      </button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="col-2 d-flex flex-column align-items-center justify-content-center">
        <form method="post">
          <input type="hidden" id="contactArray" name="contactId">
          <input type="hidden" name="pagesNw" value="<?php echo $nCont ?>">
          <button id="AddConts" type="submit" name="AddContacts" class="btn btn-light" onclick="unselectWl()"><img src="../Assets/arrow-left.png" alt="Arrow" style="transform:rotate(180deg)"></button>
        </form>
        <h6 id="AddText" class="mt-5 mb-5 text-success">Add selected</h6>
        <form method="post">
          <input type="hidden" id="costumerArray" name="costumerId">
          <input type="hidden" name="pageNl" value="<?php echo $nCont ?>">
          <button id="AddCost" type="submit" name="AddCostumers" class="btn btn-light" onclick="unselectLc()"><img src="../Assets/arrow-left.png" alt="Arrow"></button>
        </form>
      </div>

      <!------------------------------------------------------------------------------------------------------------>
      <!----------------------------------------- Costumers Table -------------------------------------------------->
      <!------------------------------------------------------------------------------------------------------------>

      <div class="col-5 rounded p-2 shadow-sm bg-body-tertiary" style="border:1px solid #e6e6e6">
        <div class="d-flex justify-content-around align-items-center mb-2 mt-2">
          <div>
            <h6>Lightspeed Customers</h6>
          </div>
          <div class="d-flex align-items-center justify-contente-center">
            <form method="post"><input class="rounded border-light" type="text" class="form-control" placeholder="Search a costumer" name="nameSearchLs"><button type="submit" class="btn btn-sm mb-2 ms-1"><img src='../Assets/search.png' alt='Search'></button></form>
          </div>
        </div>
        <table class="table table-striped table-hover caption-top align-middle text-center">
          <thead>
            <tr>
              <th scope="col"></th>
              <th scope="col">Name</th>
              <th scope="col">Date</th>
              <th scope="col"></th>

            </tr>
          </thead>
          <tbody id="contactTable2">
            <?php
            if ($costumers != [null]) {
              foreach ($costumers as $key => $costu) {
                $date = explode('T', $costu->createTime);
            ?>
                <tr>
                  <td>
                    <form method="post" style="margin:0;">
                      <input type="hidden" name="costumerId" value='<?php echo json_encode([$costu->customerID]); ?>'>
                      <input type="hidden" name="pageNl" value="<?php echo $nContLs ?>">
                      <button style="border:none; background:none" name="AddCostumers">
                        <img src='../Assets/arrow-left.png' alt='Arrow'>
                      </button>
                    </form>
                  </td>
                  <td><?php if($costu->firstName == '' && $costu->lastName == ''){echo $costu->Contact->Phones->ContactPhone->number;}else{ echo $costu->firstName . " " . $costu->lastName;}?></td>
                  <td><?php echo $date[0]; ?></td>
                  <td><input type="checkbox" name="selectLCostumer" id="selectLCostumer" onchange="SelectedCLs('<?php echo $costu->customerID ?>')" value="<?php echo $costu->customerID ?>"></td>
                </tr>

              <?php
              }
            } else {
              ?>
              <tr>
                <td colspan="4">No existing costumers</td>
              </tr>
            <?php
            };
            ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <form method="post" style="margin:0;">
                      <input type="hidden" id="prevLs" name="prevLs" value="<?php echo $reqCost->{'@attributes'}->previous ?>">
                      <button type="submit" class="btn btn-light" <?php if ($reqCost->{'@attributes'}->previous == '') {
                                                                    echo "disabled";
                                                                  } ?>>
                        < preview
                          </button>
                    </form>
                  </div>
                  <div><?php echo 'Page ' . $nContLs ?></div>
                  <div>
                    <form method="post" style="margin:0;">
                      <input type="hidden" id="nextLs" name="nextLs" value="<?php echo $reqCost->{'@attributes'}->next ?>">
                      <button type="submit" class="btn btn-light" type="submit" <?php if ($reqCost->{'@attributes'}->next == '') {
                                                                                  echo "disabled";
                                                                                } ?>>
                        next >
                      </button>
                    </form>
                  </div>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  let ctx = document.getElementById("contactTable");
  let ctx1 = document.getElementById("contactTable2");
  let selected = document.getElementById("contactsSelected");
  let checked = document.getElementById("check");
  let inpHidWl = document.querySelector("input[id = 'contactArray']");
  let inpHidLs = document.querySelector("input[id = 'costumerArray']");
  let addText = document.querySelector("#AddText");
  let switchWl = document.querySelector("#flexSwitchCheckContacts");
  let switchLs = document.querySelector("#flexSwitchCheckCustomers");
  let wlNextButton = document.querySelector("#nextPageData");
  let switchLValue = <?php echo json_encode($switchLValue, JSON_HEX_TAG); ?>;
  let switchWValue = <?php echo json_encode($switchWValue, JSON_HEX_TAG); ?>;
  let nextPage = <?php echo json_encode($nextpage, JSON_HEX_TAG); ?>;
  let scroll = <?php echo json_encode($goToTables, JSON_HEX_TAG); ?>;

  if (scroll == true) {
      location.href = "#CenterOnChange";
    }

  // Stringifys the array to next page because json_encode from php was not working well
  wlNextButton.value = JSON.stringify(nextPage);

  // Close alerts automaticly
  let btnClose = document.querySelector('.btn-close')

  let alert = document.querySelector('.alert')

  function closeModal() {
    btnClose.click();
  }

  if (alert =! null) {
    setTimeout(closeModal, 3000)
  }

  // Sets the value of the button and triggers the sync function for LS 
  if (switchLValue == true) {
    switchLs.checked = true;
  } else {
    switchLs.checked = false;
  }

  switchLs.addEventListener("change", function(e) {
    if (this.checked == true) {
      location.replace("/contacts?switchCost=onSwitch&switchCont=offSwitch")
    } else {
      location.replace("/contacts?switchCost=offSwitch")
    }
  })

  // Sets the value of the button and triggers the sync function for WL
  if (switchWValue == true) {
    switchWl.checked = true;
  } else {
    switchWl.checked = false;
  }

  switchWl.addEventListener("change", function(e) {
    if (this.checked == true) {
      location.replace("/contacts?switchCont=onSwitch&switchCost=offSwitch")
    } else {
      location.replace("/contacts?switchCont=offSwitch")
    }
  })

  //------------------------ Select Wl Contacts system ------------------------------

  let checkboxW = document.querySelectorAll("input[id = 'selectWContact']");
  let buttonCont = document.querySelector("#AddConts");

  //unselects all contacts
  function unselectWl() {
    localStorage.removeItem('selectedCWl');
  }

  let arrayContacts = [];

  //disables the button if there are no contacts selected and sets the variable, if exists already contacts selected, get's it from localStorage and decode it's
  if (localStorage.getItem('selectedCWl') === null || localStorage.getItem('selectedCWl') == '') {
    arrayContacts = [];
    buttonCont.disabled = true;
  } else {
    let locStorageW = localStorage.getItem('selectedCWl')
    arrayContacts = JSON.parse(locStorageW);
  }

  //funtion to select and push to an array the contacts selected, and then encode it's to fit in the localStorage variable
  function SelectedWlC(e) {

    if (!arrayContacts.includes(e)) {
      arrayContacts.push(e);
    } else {
      arrayContacts.splice(arrayContacts.indexOf(e), 1)
    }

    //disable checkboxes if is selected 10
    if (arrayContacts.length >= 10) {
      checkboxW.forEach(v => {
        if (v.checked === false) {
          v.disabled = true;
        }
      })
    } else {
      checkboxW.forEach(v => {
        v.disabled = false;
      })
    }

    //disable button to send selected contacts
    if (arrayContacts.length == 0) {
      buttonCont.disabled = true;
    } else {
      buttonCont.disabled = false;
    }

    let string = JSON.stringify(arrayContacts);
    localStorage.setItem('selectedCWl', string);

    //add all selected contacts to an input on form
    inpHidWl.value = localStorage.getItem('selectedCWl');
  }

  //the logic have to be the same whether you click on the box or not, I had to repeat both inside and outside
  if (arrayContacts.length >= 10) {
    checkboxW.forEach(v => {
      if (v.checked === false) {
        v.disabled = true;
      }
    })
  } else {
    checkboxW.forEach(v => {
      v.disabled = false;
    })
  }

  if (arrayContacts.length == 0) {
    buttonCont.disabled = true;
  } else {
    buttonCont.disabled = false;
  }

  if (arrayContacts.length > 0) {
    arrayContacts.forEach(e => {
      checkboxW.forEach(v => {
        if (e == v.value) {
          v.checked = true;
          v.disabled = false;
        }
      })
    })
  }

  inpHidWl.value = localStorage.getItem('selectedCWl');

  //------------------------ Select Ls Costumers system ------------------------------
  //have the same logic as above, are similar
  let checkboxL = document.querySelectorAll("input[id = 'selectLCostumer']");
  let buttonCost = document.querySelector("#AddCost");

  function unselectLc() {
    localStorage.removeItem('selectedCLs');
  }

  let arrayCustomers = [];

  if (localStorage.getItem('selectedCLs') === null || localStorage.getItem('selectedCLs') == '') {
    arrayCustomers = [];
    buttonCost.disabled = true;
  } else {
    let cLStorage = localStorage.getItem('selectedCLs')
    arrayCustomers = JSON.parse(cLStorage);
  }

  function SelectedCLs(e) {

    if (!arrayCustomers.includes(e)) {
      arrayCustomers.push(e);
    } else {
      arrayCustomers.splice(arrayCustomers.indexOf(e), 1)
    }

    if (arrayCustomers.length >= 10) {
      checkboxL.forEach(v => {
        if (v.checked === false) {
          v.disabled = true;
        }
      })
    } else {
      checkboxL.forEach(v => {
        v.disabled = false;
      })
    }

    if (arrayCustomers.length == 0) {
      buttonCost.disabled = true;
    } else {
      buttonCost.disabled = false;
    }

    let string = JSON.stringify(arrayCustomers);
    localStorage.setItem('selectedCLs', string);

    inpHidLs.value = localStorage.getItem('selectedCLs');
  }

  if (arrayCustomers.length >= 10) {
    checkboxL.forEach(v => {
      if (v.checked === false) {
        v.disabled = true;
      }
    })
  } else {
    checkboxL.forEach(v => {
      v.disabled = false;
    })
  }

  if (arrayCustomers.length == 0) {
    buttonCost.disabled = true;
  } else {
    buttonCost.disabled = false;
  }

  if (arrayCustomers.length > 0) {
    arrayCustomers.forEach(e => {
      checkboxL.forEach(v => {
        if (e == v.value) {
          v.checked = true;
          v.disabled = false;
        }
      })
    })
  }
  inpHidLs.value = localStorage.getItem('selectedCLs');
</script>
<?php
require_once("Includes/footer.php");
?>

</html>