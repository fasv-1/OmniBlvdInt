<?php
include_once "Router/routerConfig.php";

$router = new Router();

//--------------- Add routes and corresponding views --------------------------

$router->addRoute('GET', '/', function () {
  require __DIR__ . '/Views/home.php';
  exit;
});

$router->addRoute('GET', '/authLs', function () {
  require __DIR__ . '/Views/authLs.php';
  exit;
});

$router->addRoute('GET', '/auth', function () {
  require __DIR__ . '/Views/auth.php';
  exit;
});


$router->addRoute('GET', '/contacts', function () {
  require __DIR__ . '/Views/contacts.php';
  exit;
});

$router->addRoute('GET', '/orders', function () {
  require __DIR__ . '/Views/orders.php';
  exit;
});

// $router->addRoute('GET', '/inventory', function () {
//   require __DIR__ . '/Views/inventory.php';
//   exit;
// });

$router->addRoute('GET', '/landing', function () {
  require __DIR__ . '/Views/landing.php';
  exit;
});

$router->addRoute('GET', '/help', function () {
  require __DIR__ . '/Views/help.php';
  exit;
});


$router->addRoute('GET', '/logout', function () {
  require __DIR__ . '/Views/logout.php';
  exit;
});
$router->addRoute('GET', '/404', function () {
  require __DIR__ . '/Views/404.php';
  exit;
});

//--------------- POST Routes -----------------------

$router->addRoute('POST', '/contacts', function () {
  require __DIR__ . '/Views/contacts.php';
  exit;
});

$router->addRoute('POST', '/orders', function () {
  require __DIR__ . '/Views/orders.php';
  exit;
});

// $router->addRoute('POST', '/inventory', function () {
//   require __DIR__ . '/Views/inventory.php';
//   exit;
// });

$router->addRoute('POST', '/help', function () {
  require __DIR__ . '/Views/help.php';
  exit;
});


$router->matchRoute();
