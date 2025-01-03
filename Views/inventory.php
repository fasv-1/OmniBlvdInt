<?php
// ---------------------------------- WORK IN PROGRESS!! ------------------------------------------------------
// ---------------------------------- Next funcionality -------------------------------------------------------

require_once("Includes/head.php");
require_once("App/Controllers/wlProducts.php");
require_once("App/Controllers/lsProducts.php");
require_once("Includes/sidebar.php");
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

$wlToken = $_SESSION['wl_token'];
$locationId = $_SESSION['wl_location'];
$wlProd = new WlProducts();

$lsToken = $_SESSION['ls_token'];
$accountId = $_SESSION['account_id'];
$lsP = new lsProducts();


//----------------------------------------------------------------
//------------------------- Functions ---------------------------- 
//----------------------------------------------------------------


function endKey($array)
{
  end($array);
  return key($array);
}

//----------------------------------------------------------------
//-------------------- Ls items Pagination ----------------------- 
//----------------------------------------------------------------

if (isset($_POST['nextLs'])) {
  $_SESSION['LsPage'] = $_POST['nextLs'];
  $_SESSION['ls_count'] += 1;
  $reqLs = $lsP->getLsProducts($lsToken, $_POST['nextLs'], '');
  $items = $reqLs->Item;

  if (isset($_SESSION['ls_count'])) {
    $nContLs = ($_SESSION['ls_count'] + 1);
  } else {
    $nContLs = 1;
  }
} elseif (isset($_POST['prevLs'])) {
  $_SESSION['LsPage'] = $_POST['prevLs'];
  $_SESSION['ls_count'] -= 1;
  $reqLs = $lsP->getLsProducts($lsToken, $_POST['prevLs'], '');
  $items = $reqLs->Item;

  if (isset($_SESSION['ls_count'])) {
    if ($_SESSION['ls_count'] == 0) {
      $nContLs = 1;
    } else {
      $nContLs = ($_SESSION['ls_count'] + 1);
    }
  }
} elseif (!isset($_POST['nextLs']) && !isset($_POST['prevLs']) && !isset($_POST['nameSearchLs']) && !isset($_POST['sendLsItem'])) {
  $reqLs = $lsP->getLsProducts($lsToken, '', '');
  $items = $reqLs->Item;

  $nContLs = 1;

  if (is_array($items) == false) {
    $itm = $items;

    $items = [];

    array_push($items, $itm);
  };

  unset($_SESSION['ls_count']);

  $_SESSION['LsPage'] = '';
}

if (isset($_POST['nameSearchLs'])) {
  $reqLs = $lsP->getLsProducts($lsToken, '', $_POST['nameSearchLs']);
  $items = $reqLs->Item;

  if (is_array($items) == false) {
    $itm = $items;

    $items = [];

    array_push($items, $itm);
  };

  $nContLs = 1;
}

//-------------------- Send Items --------------------------------

if (isset($_POST['sendLsItem']) && isset($_POST['pageN'])  && isset($_POST['itemId'])) {

  $selectedItems = json_decode($_POST['itemId']);

  if (isset($_SESSION['LsPage'])) {
    $reqLs = $lsP->getLsProducts($lsToken, $_SESSION['LsPage'], '');
    $items = $reqLs->Item;
  }

  foreach ($selectedItems as $selcItem) {
    $allItem = $lsP->getProductById($selcItem, $lsToken, $accountId);
    $getItem = $allItem->Item;
    if (isset($_POST['avaibility'])) {
      $lscreation = $wlProd->addWlProducts($getItem, $_POST['productType'], 'Store', $wlToken, $locationId);
    } else {
      $lscreation = $wlProd->addWlProducts($getItem, $_POST['productType'], 'NoStore',  $wlToken, $locationId);
    }
  }

  $nContLs = $_POST['pageN'];
  $_SESSION['ls_count'] = $_POST['pageN'] - 1;
}

//----------------------------------------------------------------
//------------------------- Wl products ---------------------------- 
//----------------------------------------------------------------

if (isset($_POST['nameSearchWl'])) {
  unset($_SESSION['pagesWl']);

  $_SESSION['firstSearch'] = $_POST['nameSearchWl'];

  $reqs = $wlProd->getWlProducts($wlToken, '', $_POST['nameSearchWl']);

  $products = $reqs->products;

  if (is_array($products) == false) {
    $itm = $products;

    $products = [];

    array_push($products, $itm);
  };

  $nCont = 1;
}

if (!isset($_POST['next']) && !isset($_POST['preview']) && !isset($_POST['nameSearchWl'])) {
  unset($_SESSION['pagesWl']);
  unset($_SESSION['firstSearch']);

  $reqs = $wlProd->getWlProducts($wlToken, '', '');

  $products = $reqs->products;

  if (is_array($products) == false) {
    $itm = $products;

    $products = [];

    array_push($products, $itm);
  };

  $nCont = 1;
}

//----------------------------------------------------------------------------------
//------------ Wl products pagination for the case off using later ----------------- 
//----------------------------------------------------------------------------------

// if (isset($_POST['next']) && !isset($_SESSION['pagesWl'])) {
//   $_SESSION['pagesWl'] = array();

//   array_push($_SESSION['pagesWl'], $_POST['next']);

//   $reqs = $wlProd->getWlProducts($wlToken, $_POST['next'], '');

//   $products = $reqs->products;

//   if ($_SESSION['pagesWl']) {
//     $nCont = (count($_SESSION['pagesWl']) + 1);
//   } else {
//     $nCont = 1;
//   }
// } elseif (isset($_POST['next']) && isset($_SESSION['pagesWl'])) {
//   array_push($_SESSION['pagesWl'], $_POST['next']);

//   $reqs = $wlProd->getWlProducts($sess, $_POST['next'], '');

//   $products = $reqs->products;

//   $nCont = (count($_SESSION['pagesWl']) + 1);
// }

// if (isset($_POST['preview']) && isset($_SESSION['pagesWl']) && count($_SESSION['pagesWl']) > 1) {
//   $keyPrev = endKey($_SESSION['pagesWl']) - 1;

//   $previewPage = $_SESSION['pagesWl'][$keyPrev];

//   $reqs = $wlProd->getWlProducts($sess, $previewPage, '');

//   $products = $reqs->products;


//   $nCont = count($_SESSION['pagesWl']);

//   array_splice($_SESSION['pagesWl'], endkey($_SESSION['pagesWl']), 1);
// } elseif (isset($_POST['preview']) && isset($_SESSION['pagesWl']) && count($_SESSION['pagesWl']) == 1) {

//   if (isset($_SESSION['firstSearch'])) {
//     $firstSearch = $_SESSION['firstSearch'];
//   } else {
//     $firstSearch = '';
//   }

//   $reqs = $wlProd->getWlProducts($sess, '', $firstSearch);

//   $products = $reqs->products;

//   $nCont = 1;

//   unset($_SESSION['pagesWl']);
// }

?>
<div class="d-flex">
  <div class="p-3" style="width:350px; height:100vh"></div>
  <div class="container-fluid d-flex flex-column align-items-center justify-content-center">
    <div class="row w-100 mt-2 text-center">
      <h6>Send up to 10 items at a time. Select or click on the arrow to send items to your application and see the changes appear in the table on the left.</h6>
    </div>
    <div class="row w-100 mt-2">

      <!------------------------------------------------------------------------------------------------------------>
      <!------------------------------------------- Wl Products Table ---------------------------------------------->
      <!------------------------------------------------------------------------------------------------------------>

      <div class="col-4 border border-body-tertiary rounded p-2 shadow-sm bg-body-tertiary">
        <div class="row p-3">
          <h6 class="col-12 d-flex justify-content-center">Recently added products</h6>
        </div>
        <table class="table table-striped table-hover caption-top align-middle text-center">
          <thead>
            <tr>
              <th>Name</th>
              <th></th>
              <th></th>
              <th>Date</th>
              <th></th>
              <th></th>
            </tr>
          </thead>
          <tbody id="contactTable">
            <?php
            if ($products != [NULL]) {
              foreach ($products as $key => $prods) {
                $date = explode('T', $prods->createdAt);
            ?>
                <tr>
                  <td><?php echo $prods->name; ?></td>
                  <td colspan="5"><?php echo $date[0]; ?></td>
                </tr>
              <?php
              }
            } else {
              ?>
              <tr>
                <td colspan="6">There are no products</td>
              </tr>
            <?php
            };
            ?>
          </tbody>
        </table>
      </div>
      <div class="col-2 d-flex flex-column align-items-center justify-content-center">
        <button id="AddPrdsLs" type="button" class="btn btn-light p-2" data-bs-toggle="modal" data-bs-target="#exampleModal" data-bs-Id="" data-bs-prodName=" items"><img src="../Assets/arrow-left.png" alt="Arrow"><span>Add selected</span></button>
      </div>

      <!------------------------------------------------------------------------------------------------------------>
      <!----------------------------------------- Ls Items Table -------------------------------------------------->
      <!------------------------------------------------------------------------------------------------------------>

      <div class="col-6 border border-body-tertiary rounded p-2 shadow-sm bg-body-tertiary">
        <div class="row p-3">
          <div class="d-flex justify-content-around align-items-center mb-2 mt-1">
            <div>
              <h6>Items</h6>
            </div>
            <div class="d-flex align-items-center justify-contente-center">
              <form method="post"><input class="rounded border-light" type="text" class="form-control" placeholder="Search an item" name="nameSearchLs"><button type="submit" class="btn btn-sm mb-2 ms-1"><img src='../Assets/search.png' alt='Search'></button></form>
            </div>
          </div>
        </div>
        <table class="table table-striped table-hover caption-top align-middle text-center">
          <thead>
            <tr>
              <th scope="col"></th>
              <th scope="col">Name</th>
              <th scope="col">Price</th>
              <th></th>
              <th></th>
              <th colspan="2" scope="col">Date</th>
              <th></th>
              <th></th>
              <th scope="col"></th>
            </tr>
          </thead>
          <tbody id="contactTable2">
            <?php
            if ($items != [NULL]) {
              foreach ($items as $item) {
                $date = explode('T', $item->timeStamp);
            ?>
                <tr>
                  <td>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModal" data-bs-Id="<?php echo $item->itemID; ?>" data-bs-prodName="<?php echo $item->description; ?>" style="border:none; background:none">
                      <img src='../Assets/arrow-left.png' alt='Arrow'>
                    </button>
                  </td>
                  <td><?php echo $item->description; ?></td>
                  <td><?php echo $item->Prices->ItemPrice[0]->amount ?></td>
                  <td colspan="6"><?php echo $date[0]; ?></td>
                  <td><input type="checkbox" name="selectLsProduct" id="selectLsProduct" onchange="SelectedLs(<?php echo $item->itemID ?>)" value="<?php echo $item->itemID ?>"></td>
                </tr>

              <?php
              }
            } else {
              ?>
              <tr>
                <td colspan="10">There are no items</td>
              </tr>
            <?php
            };
            ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="10">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <form method="post" style="margin:0;">
                      <input type="hidden" id="prevLs" name="prevLs" value="<?php echo $reqLs->{'@attributes'}->previous ?>">
                      <button type="submit" class="btn btn-light" <?php if ($reqLs->{'@attributes'}->previous == '') {
                                                                    echo "disabled";
                                                                  } ?>>
                        < preview
                          </button>
                    </form>
                  </div>
                  <div><?php echo 'Page ' . $nContLs ?></div>
                  <div>
                    <form method="post" style="margin:0;">
                      <input type="hidden" id="nextLs" name="nextLs" value="<?php echo $reqLs->{'@attributes'}->next ?>">
                      <button type="submit" class="btn btn-light" <?php if ($reqLs->{'@attributes'}->next == '') {
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

      <!------------------------------------------------------------------------------------------------------------>
      <!----------------------------------------- Modal to set product type ---------------------------------------->
      <!------------------------------------------------------------------------------------------------------------>

      <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="exampleModalLabel">Add product to you App</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form method='POST'>
                <div class="mb-3">
                  <label for="productType">Select a product(s) type</label>
                  <select class="form-select" aria-label="Default select example" name="productType" id="productType">
                    <option selected value="DIGITAL">DIGITAL</option>
                    <option value="PHYSICAL">PHYSICAL</option>
                    <option value="SERVICE">SERVICE</option>
                  </select>
                </div>
                <div class="mb-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="" id="avaibilityCheck" name="avaibility" checked>
                    <label class="form-check-label" for="flexCheckDefault">
                      Available In Store
                    </label>
                  </div>
                </div>
                <input type="hidden" name="pageN" value="<?php echo $nContLs; ?>">
                <input type="hidden" name="itemId" id="recipient-id">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary" onclick="unselectAll()" name="sendLsItem">Add item</button>
            </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>


<script>
  let checkbox = document.querySelectorAll("input[id = 'selectLsProduct']");
  let buttonProds = document.querySelector("#AddPrdsLs");

  //------------------------ Select Ls Items system ------------------------------
  function unselectAll() {
    localStorage.removeItem('selectedLs');
  }

  let arrayItems = [];

  if (localStorage.getItem('selectedLs') === null || localStorage.getItem('selectedLs') == '') {
    arrayItems = [];
    buttonProds.disabled = true;
  } else {
    let locStorage = localStorage.getItem('selectedLs')
    arrayItems = JSON.parse(locStorage);
  }

  function SelectedLs(e) {

    if (!arrayItems.includes(e)) {
      arrayItems.push(e);
    } else {
      arrayItems.splice(arrayItems.indexOf(e), 1)
    }

    if (arrayItems.length >= 10) {
      checkbox.forEach(v => {
        if (v.checked === false) {
          v.disabled = true;
        }
      })
    } else {
      checkbox.forEach(v => {
        v.disabled = false;
      })
    }

    if (arrayItems.length == 0) {
      buttonProds.disabled = true;
    } else {
      buttonProds.disabled = false;
    }

    let string = JSON.stringify(arrayItems);
    localStorage.setItem('selectedLs', string);

  }

  if (arrayItems.length >= 10) {
    checkbox.forEach(v => {
      if (v.checked === false) {
        v.disabled = true;
      }
    })
  } else {
    checkbox.forEach(v => {
      v.disabled = false;
    })
  }

  if (arrayItems.length == 0) {
    buttonProds.disabled = true;
  } else {
    buttonProds.disabled = false;
  }

  if (arrayItems.length > 0) {
    arrayItems.forEach(e => {
      checkbox.forEach(v => {
        if (e == v.value) {
          v.checked = true;
          v.disabled = false;
        }
      })
    })
  }

  //------------------------------------------------------------------

  const exampleModal = document.getElementById('exampleModal')
  if (exampleModal) {
    exampleModal.addEventListener('show.bs.modal', event => {
      // Button that triggered the modal
      const button = event.relatedTarget
      // Extract info from data-bs-* attributes
      const prodId = button.getAttribute('data-bs-Id')
      const prodName = button.getAttribute('data-bs-prodName')
      // If necessary, you could initiate an Ajax request here
      // and then do the updating in a callback.

      // Update the modal's content.
      const modalTitle = exampleModal.querySelector('.modal-title')
      const modalBodyInput = exampleModal.querySelector('.modal-body #recipient-id')

      modalTitle.textContent = `Add ${prodName} to your app`
      if (prodId == '') {
        modalBodyInput.value = localStorage.getItem('selectedLs')
      } else {
        modalBodyInput.value = JSON.stringify([prodId])
      }
    })
  }

  //--------------- Close popup windows ------------------------- 
  const btnClose = document.querySelector('.btn-close')

  const alert = document.querySelector('.alert')

  function closeModal() {
    btnClose.click();
  }

  if (alert) {
    setTimeout(closeModal, 3000)
  }
</script>
<?php
require_once("Includes/footer.php");
?>

</html>