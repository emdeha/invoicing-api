<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice;

use PHPUnit\Framework\TestCase;

final class SumInvoicesTest extends TestCase
{
    private SumInvoices\UseCase $sumInvoices;

    /**
     * @throws SumInvoices\MissingParentException
     * @throws SumInvoices\MissingCurrencyException
     */
    public function testDoesNothingWithoutInvoices(): void
    {
        $this->assertCount(
            0,
            $this->sumInvoices->do([], [], "USD"),
        );
    }

    /**
     * @throws SumInvoices\MissingParentException
     * @throws SumInvoices\MissingCurrencyException
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

        $sum = $this->sumInvoices->do($invoiceList, $currencyList, $outputCurrency);
        $this->assertCount(1, $sum);

        $this->assertEquals("Vendor 1", $sum[0]->customer);
        $this->assertEquals("1000000257", $sum[0]->documentSums[0]->documentNumber);
        $this->assertEquals(400, $sum[0]->documentSums[0]->sum);
    }

    /**
     * @throws SumInvoices\MissingParentException
     * @throws SumInvoices\MissingCurrencyException
     */
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

        $sum = $this->sumInvoices->do($invoiceList, $currencyList, $outputCurrency);
        $this->assertCount(3, $sum);

        $vendorOne = $sum[0];
        $this->assertEquals("Vendor 1", $vendorOne->customer);
        $this->assertCount(2, $vendorOne->documentSums);
        $this->assertEquals("1000000257", $vendorOne->documentSums[0]->documentNumber);
        $this->assertEquals(385.76, $vendorOne->documentSums[0]->sum);
        $this->assertEquals("1000000264", $vendorOne->documentSums[1]->documentNumber);
        $this->assertEquals(1822.32, $vendorOne->documentSums[1]->sum);

        $vendorTwo = $sum[1];
        $this->assertEquals("Vendor 2", $vendorTwo->customer);
        $this->assertCount(1, $vendorTwo->documentSums);
        $this->assertEquals("1000000258", $vendorTwo->documentSums[0]->documentNumber);
        $this->assertEquals(800.23, $vendorTwo->documentSums[0]->sum);

        $vendorThree = $sum[2];
        $this->assertCount(1, $vendorThree->documentSums);
        $this->assertEquals("Vendor 3", $vendorThree->customer);
        $this->assertEquals("1000000259", $vendorThree->documentSums[0]->documentNumber);
        $this->assertEquals(1413.90, $vendorThree->documentSums[0]->sum);
    }

    /**
     * @throws SumInvoices\MissingParentException
     * @throws SumInvoices\MissingCurrencyException
     */
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

        $sum = $this->sumInvoices->do($invoiceList, $currencyList, $outputCurrency, $vatNumber);
        $this->assertCount(1, $sum);

        $vendorOne = $sum[0];
        $this->assertEquals("Vendor 1", $vendorOne->customer);
        $this->assertCount(2, $vendorOne->documentSums);
        $this->assertEquals("1000000257", $vendorOne->documentSums[0]->documentNumber);
        $this->assertEquals(385.76, $vendorOne->documentSums[0]->sum);
        $this->assertEquals("1000000264", $vendorOne->documentSums[1]->documentNumber);
        $this->assertEquals(1822.32, $vendorOne->documentSums[1]->sum);
    }

    /**
     * @throws SumInvoices\MissingCurrencyException
     */
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
        $this->sumInvoices->do($invoiceList, $currencyList, $outputCurrency);
    }

    /**
     * @throws SumInvoices\MissingParentException
     */
    public function testMissingCurrency(): void
    {
        $invoiceList = [
            new SumInvoices\InvoiceLine("Vendor 2", "987654321", "1000000258", 1, "", "EUR", 900),
            new SumInvoices\InvoiceLine("Vendor 3", "123465123", "1000000259", 1, "", "GBP", 1300),
            new SumInvoices\InvoiceLine("Vendor 1", "123456789", "1000000260", 1, "", "BGN", 100),
        ];
        $currencyList = [
            new SumInvoices\ExchangeRate("EUR", 1),
            new SumInvoices\ExchangeRate("USD", 0.987),
            new SumInvoices\ExchangeRate("GBP", 0.878)
        ];
        $outputCurrency = "GBP";

        $this->expectException(SumInvoices\MissingCurrencyException::class);
        $this->sumInvoices->do($invoiceList, $currencyList, $outputCurrency);
    }

    protected function setUp(): void
    {
        $this->sumInvoices = new SumInvoices\UseCase();
    }
}
