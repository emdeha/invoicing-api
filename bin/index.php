<?php

declare(strict_types=1);

use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();

$app->add(new BasePathMiddleware($app));

$sumInvoicesUseCase = new \InvoicingAPI\Invoice\SumInvoices\UseCase();

$view = new \InvoicingAPI\Invoice\View($app, $sumInvoicesUseCase);
$view->registerHandlers();

$app->run();
