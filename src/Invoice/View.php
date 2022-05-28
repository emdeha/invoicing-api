<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandlerInterface;
use OpenTracing;

class View
{
    private RequestHandlerInterface $handler;
    private SumInvoices\UseCase $useCase;
    private OpenTracing\Tracer $tracer;

    public function __construct(
        RequestHandlerInterface $handler,
        SumInvoices\UseCase $useCase,
        OpenTracing\Tracer $tracer
    )
    {
        $this->handler = $handler;
        $this->useCase = $useCase;
        $this->tracer = $tracer;
    }

    public function registerHandlers(): void
    {
        $this->handler->post('/api/invoices/calculate', array($this, 'sumInvoicesHandler'));
    }

    public function sumInvoicesHandler(Request $request, Response $response)
    {
        $scope = $this->tracer->startActiveSpan('sumInvoicesHandler');

        $csvFile = $request->getUploadedFiles()['csvFile'];
        try {
            $invoiceLines = CsvParser::parseToInvoiceLines($csvFile->getStream()->detach());
        } catch (InvalidCsvValueException) {
            return $this->returnValidationError("Invalid Csv Value", $response);
        } catch (InvalidCsvHeaderException) {
            return $this->returnValidationError("Invalid Csv Header", $response);
        }

        $currencyData = $request->getUploadedFiles()['currencyData']->getStream()->__toString();
        $requestData = json_decode($currencyData);

        try {
            $exchangeRates = View::getExchangeRatesFromCurrencyData($requestData);
            $outputCurrency = View::getOutputCurrencyFromCurrencyData($requestData);
            $vatNumber = View::getVatNumberFromCurrencyData($requestData);
        } catch (InvalidRequestException) {
            return $this->returnValidationError("Invalid Request", $response);
        }

        try {
            $customerSum = $this->useCase->do(
                $invoiceLines,
                $exchangeRates,
                $outputCurrency,
                $vatNumber
            );
        } catch (SumInvoices\MissingParentException) {
            return $this->returnValidationError("Missing Parent", $response);
        } catch (SumInvoices\MissingCurrencyException) {
            return $this->returnValidationError("Missing Currency", $response);
        }

        $response->getBody()->write(json_encode($customerSum) . "\n");

        $scope->close();

        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    private function returnValidationError(string $errorString, Response $response): Response
    {
        $response
            ->withStatus(400)
            ->withHeader('content-type', 'application/json')
            ->getBody()->write("{\"error\": $errorString}");
        return $response;
    }

    /**
     * @throws InvalidRequestException
     */
    private static function getExchangeRatesFromCurrencyData(object $requestData): array
    {
        if (!property_exists($requestData, 'exchangeRates')) {
            throw new InvalidRequestException();
        }

        $exchangeRates = [];
        foreach ($requestData->exchangeRates as $currency => $rate) {
            $exchangeRates[] = new SumInvoices\ExchangeRate($currency, $rate);
        }
        return $exchangeRates;
    }

    /**
     * @throws InvalidRequestException
     */
    private static function getOutputCurrencyFromCurrencyData(object $requestData)
    {
        if (!property_exists($requestData, 'outputCurrency')) {
            throw new InvalidRequestException();
        }

        return $requestData->outputCurrency;
    }

    private static function getVatNumberFromCurrencyData(object $requestData)
    {
        if (!property_exists($requestData, 'vatNumber')) {
            return null;
        }

        return $requestData->vatNumber;
    }
}

class InvalidRequestException extends Exception
{
}
