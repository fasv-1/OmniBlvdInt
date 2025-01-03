<?php
require_once("Includes/head.php");

//unset Session variable, coockies and destroy the session
session_start();
session_unset();
session_destroy();
session_write_close();
setcookie(session_name(), '', 0, '/');
?>

<body>
  <!----------------------------------------------------------------------------------------------------->
    <!---------------------------------- Logout page html  ---------------------------------------------->
    <!--------------------------------------------------------------------------------------------------->
  <section id="main-intro" class="bg-success vh-100 " style="background:url('../Assets/O.png') left top repeat; background-size:40px 40px">
    <div class="d-flex align-items-center" style="background:linear-gradient(87deg, rgba(0,0,0,0.711922268907563) 0%, rgba(255,255,255,0) 50%, rgba(0,0,0,0.7483368347338936) 100%); height:100vh; z-index:1">
      <div class="container mx-auto" style="width:70%">
        <div class="row d-flex justify-content-center bg-white pt-5 pb-5 rounded">
          <div class="row text-center">
            <h3>
              Living now?
            </h3>
            <h1>
              Thank you for using our app.
            </h1>
            <h6>Take a minute to check our main app, we have amazing solutions to empower and scale your business.</h6>
          </div>
          <div class="row mt-4 mb-2"><a href="https://omniblvd.com/" target="_blank" class="d-flex justify-content-center"><img src="https://storage.googleapis.com/msgsndr/B5YhFyrzXKL5sDbhdlQa/media/675489ac29695a361e6fc955.png " width="480" alt="Omni-Logo"></a></div>

          <div class="row d-flex justify-content-center mt-5 mb-5">
            <div class="text-center">
              <a href="/landing">
                <button type="button" class="btn btn-success btn-lg">
                  <h6>Ups, i forgot something. Login to integration app.</h6>
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