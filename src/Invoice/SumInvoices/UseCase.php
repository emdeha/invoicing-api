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
            $sumPerCustomer[$invoice->customer] = 0;
        }
        foreach ($invoiceLines as $invoice) {
            $sumPerCustomer[$invoice->customer] += UseCase::calculateInvoiceLine(
                $invoice,
                $exchangeRates,
                $outputCurrency
            );
        }

        $customerSums = [];
        foreach ($sumPerCustomer as $customer => $sum) {
            $sum /= ExchangeRate::getRateForCurrency($outputCurrency, $exchangeRates);
            $sum = round($sum, 2);
            array_push($customerSums, new CustomerSum($customer, $sum));
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