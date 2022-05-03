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
     *  * validate vat and document number
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
        $this->assertEquals($sum[0]->documentSums[0]->documentNumber, "1000000257");
        $this->assertEquals($sum[0]->documentSums[0]->sum, 400);
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

        $vendorOne = $sum[0];
        $this->assertEquals($vendorOne->customer, "Vendor 1");
        $this->assertCount(2, $vendorOne->documentSums);
        $this->assertEquals($vendorOne->documentSums[0]->documentNumber, "1000000257");
        $this->assertEquals($vendorOne->documentSums[0]->sum, 385.76);
        $this->assertEquals($vendorOne->documentSums[1]->documentNumber, "1000000264");
        $this->assertEquals($vendorOne->documentSums[1]->sum, 1822.32);

        $vendorTwo = $sum[1];
        $this->assertEquals($vendorTwo->customer, "Vendor 2");
        $this->assertCount(1, $vendorTwo->documentSums);
        $this->assertEquals($vendorTwo->documentSums[0]->documentNumber, "1000000258");
        $this->assertEquals($vendorTwo->documentSums[0]->sum, 800.23);

        $vendorThree = $sum[2];
        $this->assertCount(1, $vendorThree->documentSums);
        $this->assertEquals($vendorThree->customer, "Vendor 3");
        $this->assertEquals($vendorThree->documentSums[0]->documentNumber, "1000000259");
        $this->assertEquals($vendorThree->documentSums[0]->sum, 1413.90);
    }

    public function testFilteringByVATNumber(): void
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
        $vatNumber = "123456789";

        $sum = SumInvoices\UseCase::do($invoiceList, $currencyList, $outputCurrency, $vatNumber);
        $this->assertCount(1, $sum);

        $vendorOne = $sum[0];
        $this->assertEquals($vendorOne->customer, "Vendor 1");
        $this->assertCount(2, $vendorOne->documentSums);
        $this->assertEquals($vendorOne->documentSums[0]->documentNumber, "1000000257");
        $this->assertEquals($vendorOne->documentSums[0]->sum, 385.76);
        $this->assertEquals($vendorOne->documentSums[1]->documentNumber, "1000000264");
        $this->assertEquals($vendorOne->documentSums[1]->sum, 1822.32);
    }

    public function testMissingParent(): void
    {
        $invoiceList = [
            new SumInvoices\InvoiceLine("Vendor 2", "987654321", "1000000258", 1, "", "EUR", 900),
            new SumInvoices\InvoiceLine("Vendor 3", "123465123", "1000000259", 1, "", "GBP", 1300),
            new SumInvoices\InvoiceLine("Vendor 1", "123456789", "1000000260", 2, "1000000257", "EUR", 100),
            new SumInvoices\InvoiceLine("Vendor 1", "123456789", "1000000261", 3, "1000000257", "GBP", 50),
        ];
        $currencyList = [
            new SumInvoices\ExchangeRate("EUR", 1),
            new SumInvoices\ExchangeRate("USD", 0.987),
            new SumInvoices\ExchangeRate("GBP", 0.878)
        ];
        $outputCurrency = "GBP";

        $this->expectException(SumInvoices\MissingParentException::class);
        $sum = SumInvoices\UseCase::do($invoiceList, $currencyList, $outputCurrency);
    }
}
