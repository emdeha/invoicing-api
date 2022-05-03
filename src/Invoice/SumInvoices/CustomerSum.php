<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice\SumInvoices;

class CustomerSum
{
    public string $customer;
    public int $sum;

    public function __construct(string $customer, int $sum)
    {
        $this->customer = $customer;
        $this->sum = $sum;
    }
}
