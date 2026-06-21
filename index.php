<?php
session_start();
require_once __DIR__ . '/helpers/Autoloader.php';
require __DIR__ . '/vendor/PHPMailer/src/Exception.php';
require __DIR__ . '/vendor/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/vendor/PHPMailer/src/SMTP.php';
$config = new Configurator();
$router = $config->getRouter();

$router->dispatch(
    $_GET['controller'] ?? '',
    $_GET['method'] ?? ''
);