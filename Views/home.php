<?php
require_once("Includes/head.php");
require_once("Includes/sidebar.php");
require_once("App/Controllers/contacts.php");
require_once("App/Controllers/customers.php");
require_once("App/Controllers/sales.php");
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

$wlToken = $_SESSION['wl_token'];


$lsToken = $_SESSION['ls_token'];

//------------------------- WL Contacts Count ------------------------------------
$contWl = new Contacts();

$reqsWl = $contWl->getContacts($wlToken, [], '');

$wlTCont = $reqsWl->total;

//------------------------- Ls Costumers Count ------------------------------------

$costLs = new Costumer();

$costLs = $costLs->countCostumers($lsToken);

//------------------------- Ls Sales ------------------------------------

if (isset($_SESSION['salesYear'])) {
  $atualYear = date('Y');
  $yearSales = $_SESSION['salesYear'];
  $lastSales = $_SESSION['lastYearSales'];
} else {
  $atualYear = date('Y');

  $lSale = new Sales();

  $yearSales = $lSale->getSalesYear($lsToken, $atualYear . '-01-01', $atualYear . '-12-31');

  $lastSales = $_SESSION['lastYearSales'];

  $_SESSION['salesYear'] = $yearSales;
}
//------------------------- Wl Orders ------------------------------------

?>
<div class="d-flex">
  <div class="p-3" style="width:350px; height:100vh"></div>
  <div class="container d-flex flex-column align-items-center mt-5">
    <div class="row">
      <div class="row text-center">
        <h3>Lightspeed POS R-Series Integration</h3>
      </div>
      <div class="row text-center mt-2 mb-3">
        <h5>Sync your contacts, inventory and generate invoices.</h5>
        <h6>Visit the Help page to submit a new feature request.</h6>
      </div>
    </div>
    <div class="row d-flex mt-3 w-100 justify-content-around">
      <div class="d-flex w-auto justify-content-center align-items-center p-2 m-1 rounded shadow-sm" style="background-color:#e6e6e6">
        <div class="bg-white p-3 rounded text-center" style="width: 350px;">
          <h1 class="text-success"><?php echo $wlTCont ?></h1>
          <h5>CRM Contacts</h5>
        </div>
      </div>
      <div class="d-flex w-auto justify-content-center align-items-center p-2 m-1 rounded shadow-sm" style="background-color:#e6e6e6">
        <div class="bg-white p-3 rounded text-center" style="width: 350px;">
          <h1 class="text-success"><?php echo $costLs ?></h1>
          <h5>Lightspeed Costumers</h5>
        </div>
      </div>
    </div>
    <div class="row w-100 d-flex justify-content-center align-items-center mt-5 mb-5">
      <div class="d-flex justify-content-center align-items-center p-2 m-1 rounded shadow-sm" style="background-color:#e6e6e6">
        <div class="bg-white p-2 rounded w-100">
          <div class="container-fluid">
            <h2 class="text-center">Lightspeed Total Gross Sales in US$ per month</h2>
            <div>
              <canvas id="myChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  let ctx = document.getElementById("myChart").getContext("2d");

  let data = <?php echo json_encode($yearSales, JSON_HEX_TAG); ?>;

  let lastData = <?php echo json_encode($lastSales, JSON_HEX_TAG); ?>;

  let year = <?php echo json_encode($atualYear, JSON_HEX_TAG); ?>;

  //Separate values by month
  let allLs = [];
  data.forEach(e => {
    let date = e.date.split('/');

    allLs.push({
      'month': date[0],
      'total': e.amount
    });
  })

  //Joins all values in the same key (month)
  const groupByTimes = data => Object.values(data.reduce((data, {
    month,
    total
  }) => {
    if (data[month]) {
      data[month].total.push(total)
    } else data[month] = {
      month,
      total: [total]
    };
    return data;
  }, {}));

  //Sums all values
  function sum(a) {
    return (a.length && parseFloat(a[0]) + sum(a.slice(1))) || 0;
  }


  let reducedLs = groupByTimes(allLs);

  let totalLs = [];

  reducedLs.forEach(e => {
    let calc = sum(e.total).toFixed(2);

    totalLs.push({
      'month': e.month,
      'sum': calc
    })
  })

  //Sort the totals in right order
  totalLs = totalLs.sort((a, b) => b.month - a.month).reverse();

  let Lsvalue = [];
  totalLs.forEach(v => {
    Lsvalue.push(v.sum)
  })

  //------------------------- Last year data ----------------------------------------

  //Separate values and month
  let allLastLs = [];
  lastData.forEach(e => {
    let date = e.date.split('/');

    allLastLs.push({
      'month': date[0],
      'total': e.amount
    });
  })

  //Joins all values in the same key (month)

  let reducedLastLs = groupByTimes(allLastLs);

  let totalLastLs = [];

  reducedLastLs.forEach(e => {
    let calc = sum(e.total).toFixed(2);

    totalLastLs.push({
      'month': e.month,
      'sum': calc
    })
  })

  //Sort the totals in right order
  totalLastLs = totalLastLs.sort((a, b) => b.month - a.month).reverse();

  let LsLastValue = [];
  totalLastLs.forEach(v => {
    LsLastValue.push(v.sum)
  })

  //------------------------- Chart config --------------------------------------------------------
  let myChart = new Chart(ctx, {
    type: "line",
    data: {
      labels: [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
      ],
      datasets: [{
          label: year,
          data: Lsvalue,
          backgroundColor: "rgba(164,16,16,0.6)",
          borderColor: "rgba(164,16,16,1)",
          fill: true,
          tension: 0.4
        },
        {
          label: year - 1,
          data: LsLastValue,
          backgroundColor: "rgba(92, 155, 191, 0.6)",
          borderColor: "rgba(92, 155, 191, 1)",
          fill: true,
          tension: 0.4
        },
      ],
    },
    options: {
      responsive: true,
      interaction: {
        mode: "index",
        intersect: false,
      },
    },
  });
</script>
<?php
require_once("Includes/footer.php");
?>

</html>