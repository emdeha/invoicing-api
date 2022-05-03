<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice\SumInvoices;

class CustomerSum
{
    public string $customer;
    public $documentSums; /* DocumentSum[] */

    public function __construct(string $customer)
    {
        $this->customer = $customer;
        $this->documentSums = [];
    }

    public function addToDocumentSum(string $documentNumber, float $invoiceLineSum): void
    {
        foreach ($this->documentSums as $sum) {
            if ($sum->documentNumber === $documentNumber) {
                $sum->sum += $invoiceLineSum;
                return;
            }
        }

        array_push($this->documentSums, new DocumentSum($documentNumber, $invoiceLineSum));
    }

    public function convertDocumentSumsToOutputRate(string $outputCurrency, $exchangeRates): void
    {
        foreach ($this->documentSums as $sum) {
            $sum->sum /= ExchangeRate::getRateForCurrency($outputCurrency, $exchangeRates);
        }
    }

    public function roundDocumentSums()
    {
        foreach ($this->documentSums as $sum) {
            $sum->sum = round($sum->sum, 2);
        }
    }
}
