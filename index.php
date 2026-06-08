<?php
session_start();
require_once __DIR__ . '/helpers/Autoloader.php';
$config = new Configurator();
$router = $config->getRouter();

$router->dispatch(
    $_GET['controller'] ?? '',
    $_GET['method'] ?? ''
);