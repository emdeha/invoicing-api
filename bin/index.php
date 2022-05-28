<?php

declare(strict_types=1);

use InvoicingAPI\Invoice\SumInvoices\UseCase;
use InvoicingAPI\Invoice\View;
use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;
use Jaeger\Config;
use OpenTracing\GlobalTracer;

require __DIR__ . '/../vendor/autoload.php';

function initializeApp($tracer): void {
    $app = AppFactory::create();

    $app->addRoutingMiddleware();

    $app->add(new BasePathMiddleware($app));

    $sumInvoicesUseCase = new UseCase();

    $view = new View($app, $sumInvoicesUseCase, $tracer);
    $view->registerHandlers();

    $app->run();
}

/**
 * @throws Exception
 */
function initializeTracing(): OpenTracing\Tracer {
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

$tracer = null;

try {
    $tracer = initializeTracing();
} catch (Exception $e) {
    print("Couldn't initialize tracer");
    exit(1);
}

initializeApp($tracer);

$tracer->flush();
