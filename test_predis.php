<?php
require 'vendor/autoload.php';  

// Predis Client configuration
$redis = new Predis\Client([
    'scheme' => 'tcp',       // Protocol TCP
    'host'   => '127.0.0.1', // localhost
    'port'   => 6379,        // Redis default port
]);

// Redis connection test
try {
    $redis->connect();
    echo "Conectado ao Redis com sucesso!";
} catch (Exception $e) {
    echo "Erro ao conectar ao Redis: " . $e->getMessage();
}
