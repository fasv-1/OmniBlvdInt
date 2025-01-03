<?php
//------------------- Side bar menu ---------------------------------------------
$uri = explode('?', $_SERVER['REQUEST_URI']);
?>
<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-success" style="width: 280px; position:fixed; height:100vh">
  <a href="https://omniblvd.com/" target="_blank" class="d-flex align-items-center mb-3 mt-2 mb-md-0 justify-content-center" onclick="clearStorage()">
    <img src="https://storage.googleapis.com/msgsndr/B5YhFyrzXKL5sDbhdlQa/media/67548a8b53bb95b240a69da0.png" alt="" width="200">
  </a>
  <a href="https://www.lightspeedhq.com/" target="_blank" class="d-flex flex-column align-items-center mb-1 mt-2 justify-content-center link-light link-underline-opacity-0" onclick="clearStorage()">
    <img src="../Assets/Lightspeed.png" alt="Logo" width="15" class="d-inline-block align-text-top">
    <p style="font-size:10px">Lightspeed POS R-Series Integration</p>
  </a>
  <hr>
  <ul class="nav nav-pills flex-column mb-auto">
    <li class="nav-item mt-3 rounded" style="<?php if ($uri[0] == "/") {
                                                echo "background-color: #115837";
                                              } ?>">
      <a href="/" class="nav-link text-white d-flex align-items-center" aria-current="page" onclick="clearStorage()">
        <img src="../Assets/graph-arrow.png" class="bi me-2" width="18" height="18">
        <b>Dashboard</b>
      </a>
    </li>
    <li class="nav-item mt-3 rounded" style="<?php if ($uri[0] == "/contacts") {
                                                echo "background-color: #115837";
                                              } ?>">
      <a href="/contacts" class="nav-link text-white d-flex align-items-center" aria-current="page" onclick="clearStorage()">
        <img src="../Assets/users.png" class="bi me-2" width="18" height="18">
        <b>Contacts | Customers</b>
      </a>
    </li>
    <li class="nav-item mt-3 rounded" style="<?php if ($uri[0] == "/orders") {
                                                echo "background-color: #115837";
                                              } ?>">
      <a href="/orders" class="nav-link text-white d-flex align-items-center" onclick="clearStorage()">
        <img src="../Assets/package.png" class="bi me-2" width="18" height="18">
        <b>Orders | Sales</b>
      </a>
    </li>
    <!-- <li class="nav-item mt-3 rounded" style="">
      <a href="/inventory" class="nav-link text-white">
        <img src="../Assets/list.png" class="bi me-1" width="18" height="18">
        <b>Inventory</b>
      </a>
    </li> -->
    <li class="nav-item mt-3 rounded" style="<?php if ($uri[0] == "/help") {
                                                echo "background-color: #115837";
                                              } ?>">
      <a href="/help" class="nav-link text-white d-flex align-items-center" onclick="clearStorage()">
        <img src="../Assets/help-circle.png" class="bi me-2" width="18" height="18">
        <b>Help</b>
      </a>
    </li>
  </ul>
  <hr>
  <div class="dropdown">

    <ul class="nav nav-pills flex-column mb-auto" aria-labelledby="dropdownUser1">
      <li class="nav-item m-3">
        <a class="dropdown-item d-flex align-items-center" href="/logout" onclick="clearStorage()">
          <img src="../Assets/power.png" alt="" class="bi me-2" width="18" height="18">
          <b>Log out</b>
        </a>
      </li>
    </ul>
  </div>
</div>

<script>
  // Clears any localStorage variables from the pages
  function clearStorage(){
    localStorage.clear();
  }
</script>