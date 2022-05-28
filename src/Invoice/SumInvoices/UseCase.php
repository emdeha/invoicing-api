<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice\SumInvoices;

use Exception;

class UseCase
{
    /**
     * @throws MissingParentException
     * @throws MissingCurrencyException
     */
    public function do(
        $invoiceLines,
        $exchangeRates,
        string $outputCurrency,
        string $vatNumber = null
    ): array
    {
        $invoiceLines = UseCase::filterByVatNumber($vatNumber, $invoiceLines);

        UseCase::validateParentsExist($invoiceLines);

        $sumPerCustomer = [];
        foreach ($invoiceLines as $invoice) {
            $invoiceLineSum = UseCase::calculateInvoiceLine(
                $invoice,
                $exchangeRates
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

    /**
     * @throws MissingCurrencyException
     */
    private static function calculateInvoiceLine(
        InvoiceLine $invoiceLine,
        $exchangeRates
    ): float {
        $sign = $invoiceLine->type === TYPE_CREDIT ? -1 : 1;
        $rateForCurrency = ExchangeRate::getRateForCurrency($invoiceLine->currency, $exchangeRates);

        return $invoiceLine->total * $rateForCurrency * $sign;
    }

    private static function addDocumentToSumPerCustomer(
        &$sumPerCustomer,
        InvoiceLine $invoiceLine,
        float $invoiceLineSum
    ): void {
        $parentDocument = $invoiceLine->type === TYPE_INVOICE
            ? $invoiceLine->documentNumber
            : $invoiceLine->parentDocument;

        if (empty($sumPerCustomer[$invoiceLine->customer])) {
            $sumPerCustomer[$invoiceLine->customer] = new CustomerSum($invoiceLine->customer);
        }

        $sumPerCustomer[$invoiceLine->customer]->addToDocumentSum($parentDocument, $invoiceLineSum);
    }

    private static function filterByVatNumber(?string $vatNumber, $invoiceLines)
    {
        if (is_null($vatNumber)) {
            return $invoiceLines;
        }

        $filteredInvoiceLines = [];
        foreach ($invoiceLines as $line) {
            if ($line->vatNumber === $vatNumber) {
                $filteredInvoiceLines[] = $line;
            }
        }
        return $filteredInvoiceLines;
    }

    /**
     * @throws MissingParentException
     */
    private static function validateParentsExist($invoiceLines): void
    {
        $parentDocumentNumbers = [];
        foreach ($invoiceLines as $line) {
            if ($line->type === TYPE_INVOICE) {
                $parentDocumentNumbers[$line->documentNumber] = true;
            }
        }

        foreach ($invoiceLines as $line) {
            if ($line->type !== TYPE_INVOICE &&
                empty($parentDocumentNumbers[$line->parentDocument])) {
                throw new MissingParentException();
            }
        }
    }
}

class MissingParentException extends Exception
{
}

class MissingCurrencyException extends Exception
{
}
