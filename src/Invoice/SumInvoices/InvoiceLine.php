<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice\SumInvoices;

define("TYPE_INVOICE", 1);
define("TYPE_CREDIT", 2);
define("TYPE_DEBIT", 3);

class InvoiceLine
{
    public string $customer;
    public string $vatNumber;
    public string $documentNumber;
    public int $type;
    public string $parentDocument;
    public string $currency;
    public int $total;

    public function __construct(
        string $customer,
        string $vatNumber,
        string $documentNumber,
        int $type,
        string $parentDocument,
        string $currency,
        int $total
    ) {
        $this->customer = $customer;
        $this->vatNumber = $vatNumber;
        $this->documentNumber = $documentNumber;
        $this->type = $type;
        $this->parentDocument = $parentDocument;
        $this->currency = $currency;
        $this->total = $total;
    }
}
