<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice\SumInvoices;

class CustomerSum
{
    public string $customer;
    public float $sum;

    public function __construct(string $customer, float $sum)
    {
        $this->customer = $customer;
        $this->sum = $sum;
    }
}
