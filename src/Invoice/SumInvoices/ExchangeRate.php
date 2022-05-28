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

    /**
     * @throws MissingCurrencyException
     */
    public static function getRateForCurrency(string $currency, $exchangeRates): float
    {
        foreach ($exchangeRates as $rate) {
            if ($rate->currency === $currency) {
                return $rate->rate;
            }
        }

        throw new MissingCurrencyException();
    }
}
