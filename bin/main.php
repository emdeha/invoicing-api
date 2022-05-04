<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$sumInvoicesUseCase = new \InvoicingAPI\Invoice\SumInvoices\UseCase();

$view = new \InvoicingAPI\Invoice\View($app, $sumInvoicesUseCase);
$view->registerHandlers();

$app->run();
