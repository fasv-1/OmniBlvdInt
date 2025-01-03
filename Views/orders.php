<?php
//----------- Files required for this page ------------------------ 
require_once("Includes/head.php");
require_once("Includes/sidebar.php");
require_once("App/Controllers/sales.php");
require_once("App/Controllers/invoices.php");
require_once("App/Controllers/poll.php");
require_once("App/Abstract/functionsWl.php");
require_once("App/Abstract/functionsLs.php");
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

$lsToken = $_SESSION['ls_token'];
$accountId = $_SESSION['account_id'];

$wlToken = $_SESSION['wl_token'];
$locationId = $_SESSION['wl_location'];

$yearSales = $_SESSION['salesYear'];

$sle = new Sales();

$in = new Invoice();

$newPoll = new Poll();

$abst = new AbstractWl();

$invoice = false;

$urlBusi = "https://services.leadconnectorhq.com/businesses/?locationId=" . $locationId;

$businesses = $abst->getSingleWlData($wlToken, $urlBusi);


$con = new con();
$sql = "SELECT * FROM wl_credentials WHERE token = ?";
$stmt = $con->conn()->prepare($sql);

//set the value off the switch button in the beginning
if ($stmt->execute([$wlToken])) {
  $r = $stmt->fetch();

  if ($r['status'] == 0) {
    $switchValue = false;
  } else {
    $switchValue = true;
  }
}

//swithes the value off the switch button and starts or ends the polling system
if (isset($_GET['switch'])) {

  if ($_GET['switch'] == 'switch') {
    $switchValue = true;

    $pollstar = $newPoll->updatePoll($switchValue, $wlToken, $accountId);
  } elseif ($_GET['switch'] == 'noswitch') {
    $switchValue = false;

    $pollstar = $newPoll->updatePoll($switchValue, $wlToken, $accountId);
  }
};

// ------------------------------------------------------------------------------------
//--------------------------------- Sales table navigation ----------------------------
//-------------------------------------------------------------------------------------

//----------------------------- Next page ---------------------------------------------

if (isset($_POST['nextLsSales'])) {
  $invoice = true;
  $_SESSION['ls_count'] += 1;
  $sales = $sle->getLastSales($lsToken, $_POST['nextLsSales'], '', '', '');
  $onlySales = $sales->Sale;

  if (isset($_SESSION['ls_count'])) {
    $nContLs = ($_SESSION['ls_count'] + 1);
  } else {
    $nContLs = 1;
  }

  //----------------------------- Previous page ------------------------------------------
} elseif (isset($_POST['prevLsSales'])) {
  $invoice = true;
  $_SESSION['ls_count'] -= 1;
  $sales = $sle->getLastSales($lsToken, $_POST['prevLsSales'], '', '', '');
  $onlySales = $sales->Sale;

  if (isset($_SESSION['ls_count'])) {
    if ($_SESSION['ls_count'] == 0) {
      $nContLs = 1;
    } else {
      $nContLs = ($_SESSION['ls_count'] + 1);
    }
  }
  //----------------------------- First page ------------------------------------------
} elseif (!isset($_POST['searchSale']) && !isset($_POST['prevLsSales']) && !isset($_POST['nextLsSales'])) {
  $sales = $sle->getLastSales($lsToken, '', '', '', '');
  $onlySales = $sales->Sale;

  $nContLs = 1;

  unset($_SESSION['ls_count']);
}

//-------------------------------------- Searched input page ------------------------------------------
if (isset($_POST['searchSale']) && isset($_POST['saleStartDate']) && isset($_POST['saleEndDate'])) {
  $starDate = $_POST['saleStartDate'];
  $endDate = $_POST['saleEndDate'];
  $sales = $sle->getLastSales($lsToken, '', $starDate, $endDate, '');
  $onlySales = $sales->Sale;
  $nContLs = 1;
}

//------------------------------------------- Create invoice area ------------------------------------------
if (isset($_POST['createInvoice']) && isset($_POST['sale_id']) && isset($_POST['invoiceName'])) {

  if (isset($_POST['liveMode'])) {
    $liveMode = true;
  } else {
    $liveMode = false;
  }

  $form = new stdClass();

  $form->invoiceName = $_POST['invoiceName'];
  $form->dueDate = $_POST['dueDate'];
  $form->sendEmail = $_POST['sendEmail'];
  $form->sendPhone = $_POST['sendPhone'];
  $form->liveMode = $liveMode;

  $saleId = intval($_POST['sale_id']);

  $abstL = new AbstractLs();

  $urlS = "https://api.lightspeedapp.com/API/V3/Account/" . $accountId . "/Sale/" . $saleId . ".json?load_relations=all";
  $sls = $abstL->getSingleLsData($lsToken, $urlS);
  $sale = $sls->Sale;

  $invoi = $in->createInvoice($locationId, $wlToken, $sale, $form);

  if ($invoi->statusCode == 422) {
    echo "<div class='alert alert-danger alert-dismissible fade show position-absolute w-100 text-center' role='alert'>" . $invoi->message . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
  } else {
    echo "<div class='alert alert-success alert-dismissible fade show position-absolute w-100 text-center' role='alert'>Invoice Created with success. Status:" . $invoi->status . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
  }
}

?>
<!-- ----------------------------------------------------------------------------------
--------------------------------- Earnings display ------------------------------------
----------------------------------------------------------------------------------- -->
<div class="d-flex">
  <div class="p-3" style="width:350px; height:100vh"></div>
  <div class="container d-flex flex-column align-items-center mt-5">

    <div class="row d-flex justify-content-start">
      <div class="d-flex w-auto justify-content-start align-items-center p-2 ps-3 pe-3 m-1 rounded" style="background-color:#e6e6e6">
        <div class="bg-white p-3 ps-5 pe-5 rounded">
          <label for="wl-app-prof">Total earnings for the year from Ls sales</label>
          <h1 id="lsYearTotal" style="font-size:70px;" class="text-center text-danger"></h1>
        </div>
      </div>
    </div>
    <!-- ----------------------------------------------------------------------------------
--------------------------------- Switch button for pull system -----------------------
----------------------------------------------------------------------------------- -->
    <div class="row w-100 d-flex pt-3 pb-3 mt-3 border-top border-bottom">
      <div class="rounded p-3  shadow-sm " style="border:1px solid #e6e6e6">
        <div class="row m-1">
          <div class="form-check form-switch p-0 d-flex align-items-center">
            <input class="form-check-input m-0" type="checkbox" role="switch" id="flexSwitchCheckDefault" style="width:70px; height:40px">
            <h4 class="ms-2 mt-1">Alert from Lightspeed Sale</h4>
          </div>
        </div>
        <div class="row text-muted pt-1">
          <p class="text-muted m-0">When this option is enabled, two custom fields will be created in your GHL Contacts: <b>"LS Sale"</b> and <b>"LS Sale Value"</b>.</p>
          <ul class="ps-5 m-0">
            <li class="text-muted m-0"><b>"LS Sale":</b> Stores the date when the sale is completed.</li>
            <li class="text-muted m-0"><b>"LS Sale Value":</b> Stores the total value of the sale.</li>
          </ul>
          <p class="text-muted">If the contact does not already exist, it will be created along with these two custom fields. These fields will be updated automatically each time a sale is completed in Lightspeed.</p>
          <br>
          <p class="text-muted m-0">This functionality is especially useful for creating tailored automations based on the sales data. For example:</p>
          <ul class="ps-5 mt-0">
            <li class="text-muted"><b>Follow-Up Sequences:</b> Trigger automated emails or SMS to thank customers for their purchase</li>
            <li class="text-muted"><b>Upsell Opportunities:</b> Set conditions to offer upgrades or related products for high-value sales.</li>
            <li class="text-muted"><b>Retention Campaigns:</b> Send reminders or promotions to customers after a specific period since their last sale.</li>
            <li class="text-muted"><b>Performance Analysis:</b> Use the sale value data to segment contacts by purchase behavior for targeted campaigns</li>
          </ul>
          <br>
          <p class="text-muted m-0">Note: The custom fields will be created for all contacts, but their use is optional.</p>

        </div>
      </div>
    </div>

    <!-- ----------------------------------------------------------------------------------
    --------------------------------- Invoices table --------------------------------------
    ----------------------------------------------------------------------------------- -->
    <div class="row w-100 d-flex p-3 mt-3 mb-5">
      <div class="row ">
        <h3>Create Invoices on your CRM</h3>
      </div>
      <div class="row mx-auto">
        <label for="table">Creat invoices on your CRM from sales made on Lightspeed (Notice: this will create a draft invoice in your CRM, always check it before sending). </label>
        <div class="rounded p-2 mt-2 shadow-sm bg-body-tertiary" style="border:1px solid #e6e6e6">
          <div class="row p-3 d-flex align-items-center">
            <h5 class="col-3 d-flex justify-content-center align-items-end">Lightspeed Sales</h5>
            <div class="col-9 d-flex justify-content-end">
              <form action="/orders" method="post" class="col-9 d-flex">
                <div class="me-1">
                  <label for="saleStartDate"> Select start date </label>
                  <input type="date" id="saleStartDate" class="form-control " name="saleStartDate">
                </div>
                <div class="me-1">
                  <label for="SaleEndDate"> Select end date </label>
                  <input type="date" id="saleEndDate" class="form-control " name="saleEndDate">
                </div>
                <div class="d-flex justify-content-center align-items-end ms-1">
                  <button type="submit" name="searchSale" class="btn btn-outline-success ">search</button>
                </div>
              </form>
            </div>
          </div>
          <table id="invoices" class="table table-striped align-middle text-center">
            <thead>
              <tr>
                <th scope="col">Id</th>
                <th scope="col">Customer name</th>
                <th scope="col">Status</th>
                <th scope="col">Total value</th>
                <th scope="col">date</th>
                <th scope="col"></th>
              </tr>
            </thead>
            <tbody id="contactTable2">
              <?php
              if ($onlySales != [Null]) {
                foreach ($onlySales as $key => $sale) {
                  $date = explode('T', $sale->timeStamp);

                  if (isset($sale->Customer)) {
                    $customerName = $sale->Customer->firstName . ' ' . $sale->Customer->lastName;
                    if (isset($sale->Customer->Contact->Emails->ContactEmail->address)) {
                      $customerEmail = $sale->Customer->Contact->Emails->ContactEmail->address;
                    } else {
                      $customerEmail = '';
                    }
                    if (isset($sale->Customer->Contact->Phones->ContactPhone->number)) {
                      $customerPhone = $sale->Customer->Contact->Phones->ContactPhone->number;
                    } else {
                      $customerPhone = '';
                    }
                  } else {
                    $customerName = '';
                  }
              ?>
                  <tr>
                    <td><?php echo $sale->saleID; ?></td>
                    <td><?php echo $customerName ?></td>
                    <td><?php if ($sale->completed == 'true') {
                          echo 'completed';
                        } else {
                          echo 'incomplete';
                        } ?></td>
                    <td><?php echo $sale->calcTotal; ?></td>
                    <td><?php echo $date[0]; ?></td>
                    <td>
                      <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModal"
                        data-bs-Id="<?php echo $sale->saleID; ?>"
                        data-bs-saleName="<?php echo $customerName; ?>"
                        data-bs-sendEmail="<?php echo $customerEmail; ?>"
                        data-bs-sendPhone="<?php echo $customerPhone; ?>"
                        <?php if ($customerName == '') {
                          echo 'disabled';
                        } ?> class="btn btn-outline-success">create invoice</button>
                    </td>
                  </tr>

                <?php
                }
              } else {
                ?>
                <tr>
                  <td colspan="6">There are no sales</td>
                </tr>
              <?php
              };
              ?>
            </tbody>
            <tfoot>
              <tr>
                <td>
                  <form method="post" style="margin:0;">
                    <input type="hidden" id="prevLs" name="prevLsSales" value="<?php echo $sales->{'@attributes'}->previous ?>">
                    <button type="submit" class="btn btn-light" <?php if ($sales->{'@attributes'}->previous == '') {
                                                                  echo "disabled";
                                                                } ?>>
                      < preview
                        </button>
                  </form>
                </td>
                <td colspan="4" class="text-center"><?php echo 'Page ' . $nContLs ?></td>
                <td>
                  <form method="post" style="margin:0;">
                    <input type="hidden" id="nextLs" name="nextLsSales" value="<?php echo $sales->{'@attributes'}->next ?>">
                    <button type="submit" class="btn btn-light" <?php if ($sales->{'@attributes'}->next == '') {
                                                                  echo "disabled";
                                                                } ?>>
                      next >
                    </button>
                  </form>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!--------------------------------------------------------------------------------------------------->
    <!---------------------------------- Modal to add aditional info for invoices -------------------------->
    <!--------------------------------------------------------------------------------------------------->

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h1 class="modal-title fs-5" id="exampleModalLabel">Add product to you App</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body m-2">
            <form class="row g-4 needs-validation" method="post" novalidate>
              <div class="col-12">
                <label for="validationInvoiceName" class="form-label">Invoice Name *</label>
                <input type="text" class="form-control" id="validationInvoiceName" name="invoiceName" placeholder="New Invoice" required>
                <div class="invalid-feedback">
                  This field is required
                </div>
              </div>
              <div class="row g-3">
                <h3>Send Info</h3>
                <div class="col-m-6">
                  <label for="validationDueDate" class="form-label">Due Date *</label>
                  <input type="date" class="form-control" id="validationDueDate" name="dueDate" min="<?php echo date("Y-m-d"); ?>" required>
                  <div class="invalid-feedback">
                    This field is required and the date can't be before today
                  </div>
                </div>
                <div class="col-md-6">
                  <label for="validationSendEmail" class="form-label">Send to e-mail *</label>
                  <input type="email" class="form-control" id="validationSendEmail" name="sendEmail" placeholder="E-mail" required>
                  <div class="invalid-feedback">
                    This field is required
                  </div>
                </div>
                <div class="col-md-6">
                  <label for="validationSendPhone" class="form-label">Send to phone *</label>
                  <input type="text" class="form-control" id="validationSendPhone" name="sendPhone" placeholder="Phone" required>
                  <div class="invalid-feedback">
                    This field is required
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="true" name="liveMode" id="liveMode">
                  <label class="form-check-label" for="liveMode">
                    Live Mode
                  </label>
                </div>
              </div>
              <div class="col-12">
                <input type="hidden" value="" name="sale_id" id="recipient-id">
                <button class="btn btn-primary" type="submit" name="createInvoice">Submit form</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script>
    let ctx = document.getElementById("invoiceTable");
    let switsh = document.getElementById("flexSwitchCheckDefault");
    let switchValue = <?php echo json_encode($switchValue, JSON_HEX_TAG); ?>;
    let scroll = <?php echo json_encode($invoice, JSON_HEX_TAG); ?>;
    let lsYearTotal = document.getElementById("lsYearTotal");
    let yearSales = <?php echo json_encode($yearSales, JSON_HEX_TAG); ?>;

   //----------------- Sets the value of the button based on DB --------------------------------
    if (switchValue == true) {
      switsh.checked = true;
    } else {
      switsh.checked = false;
    }

    //----------------- Post method to change values in php --------------------------------
    switsh.addEventListener("change", function(e) {
      if (this.checked == true) {
        location.replace("/orders?switch=switch")
      } else {
        location.replace("/orders?switch=noswitch")
      }
    })
    //-----------------------------------------------------------------------------------------
    //------------------------- Scrolls to the table -----------------------------------------
    if (scroll == true) {
      location.href = "#invoices";
    }

    function sum(a) {
      return (a.length && parseFloat(a[0]) + sum(a.slice(1))) || 0;
    }

    //------------------- Sum value of the all year sale --------------------
    let lsAmounts = []

    yearSales.forEach(y => {
      lsAmounts.push(y.amount);
    })

    let yearTotal = sum(lsAmounts).toFixed(2);

    lsYearTotal.innerHTML += yearTotal + ' USD';

    //-------------------- Modal Invoices ------------------------------ 

    const exampleModal = document.getElementById('exampleModal')
    if (exampleModal) {
      exampleModal.addEventListener('show.bs.modal', event => {
        // Button that triggered the modal
        const button = event.relatedTarget
        // Extract info from data-bs-* attributes
        const saleId = button.getAttribute('data-bs-Id')
        const saleName = button.getAttribute('data-bs-saleName')
        const saleSendEmail = button.getAttribute('data-bs-sendEmail')
        const saleSendPhone = button.getAttribute('data-bs-sendPhone')

        // Update the modal's content.
        const modalTitle = exampleModal.querySelector('.modal-title')
        const modalIdInput = exampleModal.querySelector('.modal-body #recipient-id')
        const modalNameInput = exampleModal.querySelector('.modal-body #validationInvoiceName')
        const modalEmailInput = exampleModal.querySelector('.modal-body #validationSendEmail')
        const modalPhoneInput = exampleModal.querySelector('.modal-body #validationSendPhone')

        modalTitle.textContent = `Create invoice for ${saleName}`
        modalIdInput.value = saleId;
        modalNameInput.value = saleId;
        modalEmailInput.value = saleSendEmail;
        modalPhoneInput.value = saleSendPhone;


      })
    }

    (() => {
      'use strict'

      // Fetch all the forms we want to apply custom Bootstrap validation styles to
      const forms = document.querySelectorAll('.needs-validation')

      // Loop over them and prevent submission
      Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
          if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
          }

          form.classList.add('was-validated')
        }, false)
      })
    })()

    //------------------------ Close popups ------------------------
    let btnClose = document.querySelector('.btn-close')

    let alert = document.querySelector('.alert')

    function closeModal() {
      btnClose.click()
    }

    if (alert = !null) {
      setTimeout(closeModal, 3000)
    }

  </script>
  <?php
  require_once("Includes/footer.php");
  ?>

  </html>