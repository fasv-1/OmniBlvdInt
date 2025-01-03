<?php
require_once("Includes/head.php");
require_once("App/Controllers/auth.php");
require_once ('App/Config/config.php');

session_start();
error_reporting(0);

//validates the code from WL
if (isset($_GET['code'])) {

  $wLcode = $_GET['code'];

  $wlAuth = new Auth();

  $wlAuth->wlToken($wLcode);
};

// Checks for authentication
if(!isset($_SESSION['wl_token']) && !isset($_SESSION['wl_location'])){
  header("location: /landing");
}
?>

<body>
  <section id="main-intro" class="bg-success vh-100 " style="background:url('../Assets/O.png') left top repeat; background-size:40px 40px">
    <div class="d-flex align-items-center" style="background:linear-gradient(87deg, rgba(0,0,0,0.711922268907563) 0%, rgba(255,255,255,0) 50%, rgba(0,0,0,0.7483368347338936) 100%); height:100vh; z-index:1">
      <div class="container mx-auto" style="width:50%">
        <div class="row d-flex justify-content-center bg-white pt-5 pb-5 rounded">
          <div class="row mt-4 mb-2"><a href="https://omniblvd.com/" target="_blank" class="d-flex justify-content-center"><img src="https://storage.googleapis.com/msgsndr/B5YhFyrzXKL5sDbhdlQa/media/675489ac29695a361e6fc955.png " width="450" alt="Omni-Logo"></a></div>
          <div class="row w-75 p-3 mt-2 mb-3">
            <div class="row d-flex justify-content-center align-items-center pt-1 pb-3">
              <div class="container-fluid d-flex justify-content-center align-items-center">
                <a href="https://www.lightspeedhq.com/" class="m-2" target="_blank">
                  <img src="../Assets/Lightspeed.png" alt="Logo" width="35" class="d-inline-block align-text-top">
                </a>
                <h4 class="m-2">Lightspeed POS R-Series</h4>
              </div>
            </div>
            <div class="row text-center">
              <h4>Integration</h4>
            </div>
          </div>
          <div class="row d-flex justify-content-center mt-5 mb-5">
            <div class="text-center">
              <?php 
              //Redirection to Lightspeed app for authentication
              $client_id = App\Config\config\ls_client_id;
              $url = "https://cloud.lightspeedapp.com/auth/oauth/authorize?response_type=code&client_id=" .$client_id. "&scope=employee:all";
              ?>
              <a href="<?php echo $url; ?>">
                <button type="button" class="btn btn-success btn-lg">
                  <h4>Sign in to your Lightspeed account</h4>
                </button>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php
  require_once("Includes/footer.php");
  ?>

  </html>