<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandlerInterface;

class View
{
    private $handler;
    private $useCase;

    public function __construct(RequestHandlerInterface $handler, SumInvoices\UseCase $useCase)
    {
        $this->handler = $handler;
        $this->useCase = $useCase;
    }

    public function registerHandlers()
    {
        $this->handler->post('/api/invoices/calculate', array($this, 'sumInvoicesHandler'));
    }

    public function sumInvoicesHandler(Request $request, Response $response, $args)
    {
        $csvFile = $request->getUploadedFiles()['csvFile'];
        $invoiceLines = [];
        try {
            $invoiceLines = CsvParser::parseToInvoiceLines($csvFile->getStream()->detach());
        } catch (InvalidCsvValueException $ex) {
            $this->returnValidationError("Invalid Csv Value", $response);
        } catch (InvalidCsvHeaderException $ex) {
            $this->returnValidationError("Invalid Csv Header", $response);
        }

        $currencyData = $request->getUploadedFiles()['currencyData']->getStream()->__toString();
        $requestData = json_decode($currencyData);

        $exchangeRates = [];
        $outputCurrency = "";
        $vatNumber = null;

        try {
            $exchangeRates = View::getExchangeRatesFromCurrencyData($requestData);
            $outputCurrency = View::getOutputCurrencyFromCurrencyData($requestData);
            $vatNumber = View::getVatNumberFromCurrencyData($requestData);
        } catch (InvalidRequestException $ex) {
            return $this->returnValidationError("Invalid Request", $response);
        }

        $customerSum = null;
        try {
            $customerSum = $this->useCase->do(
                $invoiceLines,
                $exchangeRates,
                $outputCurrency,
                $vatNumber
            );
        } catch (SumInvoices\MissingParentException $ex) {
            return $this->returnValidationError("Missing Parent", $response);
        } catch (SumInvoices\MissingCurrencyException $ex) {
            return $this->returnValidationError("Missing Currency", $response);
        }

        $response->getBody()->write(json_encode($customerSum) . "\n");

        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    private function returnValidationError(string $errorString, Response &$response)
    {
        $response
            ->withStatus(400)
            ->withHeader('content-type', 'application/json')
            ->getBody()->write("{\"error\": $errorString}");
        return $response;
    }

    private static function getExchangeRatesFromCurrencyData($requestData)
    {
        if (!array_key_exists('exchangeRates', $requestData)) {
            throw new InvalidRequestException();
        }

        $exchangeRates = [];
        foreach ($requestData->exchangeRates as $currency => $rate) {
            array_push($exchangeRates, new SumInvoices\ExchangeRate($currency, $rate));
        }
        return $exchangeRates;
    }

    private static function getOutputCurrencyFromCurrencyData($requestData)
    {
        if (!array_key_exists('outputCurrency', $requestData)) {
            throw new InvalidRequestException();
        }

        return $requestData->outputCurrency;
    }

    private static function getVatNumberFromCurrencyData($requestData)
    {
        if (!array_key_exists('vatNumber', $requestData)) {
            return null;
        }

        return $requestData->vatNumber;
    }
}

class InvalidRequestException extends \Exception
{
};
