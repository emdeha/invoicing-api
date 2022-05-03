<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice\SumInvoices;

class UseCase
{
    public static function do(
        $invoiceLines,
        $exchangeRates,
        string $outputCurrency
    ) /* : CustomerSum[] */
    {
        $sumPerCustomer = [];
        foreach ($invoiceLines as $invoice) {
            $invoiceLineSum = UseCase::calculateInvoiceLine(
                $invoice,
                $exchangeRates,
                $outputCurrency
            );
            $parentDocument = $invoice->parentDocument ?: $invoice->documentNumber;

            if (empty($sumPerCustomer[$invoice->customer])) {
                $sumPerCustomer[$invoice->customer] = new CustomerSum($invoice->customer);
            }

            $sumPerCustomer[$invoice->customer]->addToDocumentSum($parentDocument, $invoiceLineSum);
        }

        $customerSums = [];
        foreach ($sumPerCustomer as $customerSum) {
            $customerSum->convertDocumentSumsToOutputRate($outputCurrency, $exchangeRates);
            $customerSum->roundDocumentSums();
            array_push($customerSums, $customerSum);
        }

        return $customerSums;
    }

    private static function calculateInvoiceLine(
        InvoiceLine $invoiceLine,
        $exchangeRates,
        string $outputCurrency
    ): float {
        $sign = $invoiceLine->type === TYPE_CREDIT ? -1 : 1;
        $rateForCurrency = ExchangeRate::getRateForCurrency($invoiceLine->currency, $exchangeRates);

        return $invoiceLine->total * $rateForCurrency * $sign;
    }
}
