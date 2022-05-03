<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice\SumInvoices;

class DocumentSum
{
    public string $documentNumber;
    public float $sum;

    public function __construct(string $documentNumber, float $sum)
    {
        $this->documentNumber = $documentNumber;
        $this->sum = $sum;
    }
}
