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
            UseCase::addDocumentToSumPerCustomer($sumPerCustomer, $invoice, $invoiceLineSum);
        }

        $customerSums = array_values($sumPerCustomer);
        foreach ($customerSums as $customerSum) {
            $customerSum->convertDocumentSumsToOutputRate($outputCurrency, $exchangeRates);
            $customerSum->roundDocumentSums();
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

    private static function addDocumentToSumPerCustomer(
        &$sumPerCustomer,
        InvoiceLine $invoice,
        float $invoiceLineSum
    ): void {
        $parentDocument = $invoice->parentDocument ?: $invoice->documentNumber;

        if (empty($sumPerCustomer[$invoice->customer])) {
            $sumPerCustomer[$invoice->customer] = new CustomerSum($invoice->customer);
        }

        $sumPerCustomer[$invoice->customer]->addToDocumentSum($parentDocument, $invoiceLineSum);
    }
}
