<?php
include_once 'App/Config/config.php';

// Redirection for WL Oauth for CRM authentication
$url = "https://marketplace.leadconnectorhq.com/oauth/chooselocation?";
$redirectUri= "http://fillthevice.pt/auth";
$cliendId = App\Config\config\wl_client_id;
$scopes = "contacts.readonly contacts.write invoices.write products.readonly products.write locations/customFields.write payments/orders.readonly products/prices.write businesses.readonly locations/customFields.readonly locations.readonly";
header("Location: " .$url. "response_type=code&redirect_uri=".$redirectUri."&client_id=".$cliendId."&scope=".$scopes);
?>