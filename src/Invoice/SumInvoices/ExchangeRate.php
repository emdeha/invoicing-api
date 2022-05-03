<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice\SumInvoices;

class ExchangeRate
{
    public string $currency;
    public float $rate;

    public function __construct(string $currency, float $rate)
    {
        $this->currency = $currency;
        $this->rate = $rate;
    }

    public static function getRateForCurrency(string $currency, $exchangeRates): float
    {
        // TODO: Validate whether the currency exists
        foreach ($exchangeRates as $rate) {
            if ($rate->currency === $currency) {
                return $rate->rate;
            }
        }
    }
}
