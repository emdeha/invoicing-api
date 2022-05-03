<?php

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$view = new \InvoicingAPI\Invoice\View($app);
$view->registerHandlers();

$app->run();
