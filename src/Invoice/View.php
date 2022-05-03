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
        $this->handler->post('/', array($this, 'sumInvoicesHandler'));
    }

    public function sumInvoicesHandler(Request $request, Response $response, $args)
    {
        $csvFile = $request->getUploadedFiles()['csvFile'];
        $invoiceLines = CsvParser::parseToInvoiceLines($csvFile->getStream()->detach());

        $currencyData = $request->getUploadedFiles()['currencyData']->getStream()->__toString();
        $requestData = json_decode($currencyData);

        $exchangeRates = View::getExchangeRatesFromCurrencyData($requestData);
        $outputCurrency = View::getOutputCurrencyFromCurrencyData($requestData);
        $vatNumber = View::getVatNumberFromCurrencyData($requestData);

        $customerSum = SumInvoices\UseCase::do(
            $invoiceLines,
            $exchangeRates,
            $outputCurrency,
            $vatNumber
        );

        $response->getBody()->write(json_encode($customerSum) . "\n");

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
