<?php
require_once("Includes/head.php");
require_once("App/Connections/mailer.php");
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

//Send an email with the contents in form
// if (isset($_POST["button"])) {
//   $first = $_POST['FirstName'];
//   $last = $_POST['LastName'];
//   $email = $_POST['Email'];
//   $message = $_POST['Message'];
//   $subject = $_POST['Subject'];

//   $email = "Hello, my name is " . $first . " " . $last . "<br><br> My email is " . $email . "<br><br> And this is my message:<br>" . $message;

//   $smtp = new Mailer();

//   $resp = $smtp->mail($email, $subject);
// }
?>
<div class="d-flex">
  <div class="p-3" style="width:350px; height:100vh"></div>
  <div class="container-fluid d-flex flex-column mt-4 mb-5">
    <div class="row">
      <div class="col-6 p-4 d-flex flex-column justify-content-center">
        <h5>The data used in this application is exclusively for the app's functionality. No data or cookies are shared with external pages or third parties.</h5>
        <br>
        <h5>If you have any questions or encounter any issues while using the app, please fill out the form below to contact our support team. We are here to help!</h5>
        <br>
        <h5><b>Thank you for using our app!</b></h5>
      </div>
      <div class="col-6 mx-auto">
        <iframe
          src="https://link.omniblvd.com/widget/form/xaeqMIqGSYJwsaWqG8ln"
          style="width:100%;height:100%;border:none;border-radius:10px"
          id="inline-xaeqMIqGSYJwsaWqG8ln"
          data-layout="{'id':'INLINE'}"
          data-trigger-type="alwaysShow"
          data-trigger-value=""
          data-activation-type="alwaysActivated"
          data-activation-value=""
          data-deactivation-type="neverDeactivate"
          data-deactivation-value=""
          data-form-name="Lightspeed Integration Feedback/Support Form"
          data-height="726"
          data-layout-iframe-id="inline-xaeqMIqGSYJwsaWqG8ln"
          data-form-id="xaeqMIqGSYJwsaWqG8ln"
          title="Lightspeed Integration Feedback/Support Form">
        </iframe>
        <script src="https://link.omniblvd.com/js/form_embed.js"></script>
        <!-- <div class="d-flex justify-content-center align-items-center p-2 rounded shadow-sm " style="background-color:#e6e6e6">
          <div class="bg-white p-3 rounded">
            <h4 class="text-success text-center">How can we help you?</h4>
            <form class="p-4 container-sm" method="post">
              <div class="row d-flex justify-content-between mb-4">
                <div class="w-100 mb-3">
                  <label for="Subject" class="form-label">Choose a subject:</label>
                  <select class="form-select " name="Subject" aria-label="Email subject" required>
                    <option value="New feature request for Lightspeed Integration App">New feature request</option>
                    <option value="Problem with Lightspeed Integration App">Problem with the app</option>
                  </select>
                </div>
                <div class="w-50 mb-3">
                  <label for="exampleInputName1" class="form-label">First name</label>
                  <input type="text" class="form-control" name="FirstName" id="exampleInputName1" placeholder="First name" aria-describedby="emailHelp" required>
                </div>
                <div class="w-50 mb-3">
                  <label for="exampleInputName1" class="form-label">Last name</label>
                  <input type="text" class="form-control" id="exampleInputName1" placeholder="Last name" name="LastName" required>
                </div>
                <div class="w-100 mb-3">
                  <label for="exampleInputEmail1" class="form-label">Email</label>
                  <input type="e-mail" class="form-control" id="exampleInputEmail1" placeholder="Email" name="Email" required>
                </div>
                <div class="w-100">
                  <label for="exampleFormControlTextarea1" class="form-label">What can we do for you?</label>
                  <textarea class="form-control" id="exampleFormControlTextarea1" placeholder="Your message here ..." rows="3" required name="Message"></textarea>
                </div>
              </div>
              <div class="row mb-2 d-flex justify-content-center">
                <button type="submit" class="btn btn-success w-25" name="button">Submit</button>
              </div>
            </form>
          </div>
        </div> -->
      </div>
    </div>
  </div>
</div>
<script>
  // const btnClose = document.querySelector('.btn-close')

  // const alert = document.querySelector('.alert')

  // function closeModal() {
  //   btnClose.click();
  // }

  // if (alert) {
  //   setTimeout(closeModal, 3000)
  // }
</script>
<?php
require_once("Includes/footer.php");
?>

</html>