<?php
//Validates code from Ls Oauth
require_once("App/Controllers/auth.php");
require_once("App/Controllers/sales.php");

if (isset($_GET['code'])) {

  //Checks the size (debug to avoid browser errors)
  if (strlen($_GET['code']) > 2048) {
    http_response_code(400);
    echo 'Code is too long.';
    exit;
  } else {
    session_start();
    
    $lscode = $_GET['code'];

    $lsAuth = new Auth();
    
    // $lsAuth->chanato();

    $lsAuth->lsToken($lscode, $_SESSION['wl_location']);


    //Get all sales from the previous year
    $lastYear = date('Y') - 1;

    $lsToken = $_SESSION['ls_token'];

    $lSale = new Sales();

    $lastSales = $lSale->getSalesYear($lsToken, $lastYear . '-01-01', $lastYear . '-12-31');

    $_SESSION['lastYearSales'] = $lastSales;

    header('Location: /');
  }
};


?>