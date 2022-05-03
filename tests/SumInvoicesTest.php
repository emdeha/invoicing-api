<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice;

use PHPUnit\Framework\TestCase;

final class SumInvoicesTest extends TestCase
{
    public function testDoesNothingWithoutInvoices(): void
    {
        $this->assertCount(
            0,
            SumInvoices\UseCase::do([], [], "USD"),
        );
    }

    /*
     * TODO:
     *  * test whether the currency is valid
     *  * test currency calculation
     *  * test missing parent
     *  * test multiple invoices
     *  * validate vat and document number
     *  * test filtering by VAT number
     */
    public function testSumsOneInvoice(): void
    {
        $invoiceList = [
            new SumInvoices\InvoiceLine("Vendor 1", "123456789", "1000000257", 1, "", "USD", 400)
        ];
        $currencyList = [
            new SumInvoices\ExchangeRate("USD", 1)
        ];
        $outputCurrency = "USD";

        $sum = SumInvoices\UseCase::do($invoiceList, $currencyList, $outputCurrency);
        $this->assertCount(1, $sum);

        $this->assertEquals($sum[0]->customer, "Vendor 1");
        $this->assertEquals($sum[0]->sum, 400);
    }

    public function testSumsManyInvoices(): void
    {
        $invoiceList = [
            new SumInvoices\InvoiceLine("Vendor 1", "123456789", "1000000257", 1, "", "USD", 400),
            new SumInvoices\InvoiceLine("Vendor 2", "987654321", "1000000258", 1, "", "EUR", 900),
            new SumInvoices\InvoiceLine("Vendor 3", "123465123", "1000000259", 1, "", "GBP", 1300),
            new SumInvoices\InvoiceLine("Vendor 1", "123456789", "1000000260", 2, "1000000257", "EUR", 100),
            new SumInvoices\InvoiceLine("Vendor 1", "123456789", "1000000261", 3, "1000000257", "GBP", 50),
            new SumInvoices\InvoiceLine("Vendor 2", "987654321", "1000000262", 2, "1000000258", "USD", 200),
            new SumInvoices\InvoiceLine("Vendor 3", "123465123", "1000000263", 3, "1000000259", "EUR", 100),
            new SumInvoices\InvoiceLine("Vendor 1", "123456789", "1000000264", 1, "", "EUR", 1600)
        ];
        $currencyList = [
            new SumInvoices\ExchangeRate("EUR", 1),
            new SumInvoices\ExchangeRate("USD", 0.987),
            new SumInvoices\ExchangeRate("GBP", 0.878)
        ];
        $outputCurrency = "GBP";

        $sum = SumInvoices\UseCase::do($invoiceList, $currencyList, $outputCurrency);
        $this->assertCount(3, $sum);

        $this->assertEquals($sum[0]->customer, "Vendor 1");
        $this->assertEquals($sum[0]->sum, 2208.09);

        $this->assertEquals($sum[1]->customer, "Vendor 2");
        $this->assertEquals($sum[1]->sum, 800.23);

        $this->assertEquals($sum[2]->customer, "Vendor 3");
        $this->assertEquals($sum[2]->sum, 1413.90);
    }
}
