<?php

declare(strict_types=1);

use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;
use Jaeger\Config;
use OpenTracing\GlobalTracer;

require __DIR__ . '/../vendor/autoload.php';

function initializeApp($tracer) {
    $app = AppFactory::create();

    $app->addRoutingMiddleware();

    $app->add(new BasePathMiddleware($app));

    $sumInvoicesUseCase = new \InvoicingAPI\Invoice\SumInvoices\UseCase();

    $view = new \InvoicingAPI\Invoice\View($app, $sumInvoicesUseCase, $tracer);
    $view->registerHandlers();

    $app->run();
}

function initializeTracing() {
    $config = new Config(
        [
            'sampler' => [
                'type' => Jaeger\SAMPLER_TYPE_CONST,
                'param' => true,
            ],
            'local_agent' => [
                'reporting_host' => 'jaeger',
                'reporting_port' => 6831
            ],
            'logging' => true,
        ],
        'invoicing-api'
    );
    $config->initializeTracer();

    return GlobalTracer::get();
}

$tracer = initializeTracing();

initializeApp($tracer);

$tracer->flush();
