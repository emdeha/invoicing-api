<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandlerInterface;

class View
{
    private $handler;

    public function __construct(RequestHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    public function registerHandlers()
    {
        $this->handler->post('/', sumInvoicesHandler);
    }

    private function sumInvoicesHandler(Request $request, Response $response, $args)
    {
        $uploadFiles = $request->getUploadedFiles();
        $dataFile = $uploadFiles['data'];

        $response->getBody()->write("The file is {$dataFile->getSize()} big.");
        return $response;
    }
}
