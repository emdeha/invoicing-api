<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice;

use phpDocumentor\Reflection\Types\Resource_;
use PHPUnit\Framework\TestCase;

final class CsvParserTest extends TestCase
{
    /**
     * @throws InvalidCsvHeaderException
     * @throws InvalidCsvValueException
     */
    public function testParsesEmptyCsv(): void
    {
        $stream = CsvParserTest::stringToStream("");

        $invoiceLines = CsvParser::parseToInvoiceLines($stream);
        $this->assertCount(0, $invoiceLines);
    }

    /**
     * @throws InvalidCsvHeaderException
     * @throws InvalidCsvValueException
     */
    public function testParseOnlyHeader(): void
    {
        $stream = CsvParserTest::stringToStream("Customer,Vat number,Document number,Type," .
            "Parent document,Currency,Total");

        $invoiceLines = CsvParser::parseToInvoiceLines($stream);
        $this->assertCount(0, $invoiceLines);
    }

    /**
     * @throws InvalidCsvHeaderException
     * @throws InvalidCsvValueException
     */
    public function testParseOneLine(): void
    {
        $stream = CsvParserTest::stringToStream(
            "Customer,Vat number,Document number,Type,Parent document,Currency,Total\n".
            "Vendor 1,123456789,1000000257,1,,USD,400"
        );

        $invoiceLines = CsvParser::parseToInvoiceLines($stream);
        $this->assertCount(1, $invoiceLines);
        $this->assertEquals("Vendor 1", $invoiceLines[0]->customer);
    }

    /**
     * @throws InvalidCsvHeaderException
     */
    public function testTypeIsNotInt(): void
    {
        $stream = CsvParserTest::stringToStream(
            "Customer,Vat number,Document number,Type,Parent document,Currency,Total\n".
            "Vendor 1,123456789,1000000257,asd1,,USD,400"
        );

        $this->expectException(InvalidCsvValueException::class);
        CsvParser::parseToInvoiceLines($stream);
    }

    /**
     * @throws InvalidCsvHeaderException
     */
    public function testTotalIsNotInt(): void
    {
        $stream = CsvParserTest::stringToStream(
            "Customer,Vat number,Document number,Type,Parent document,Currency,Total\n".
            "Vendor 1,123456789,1000000257,1,,USD,asd400"
        );

        $this->expectException(InvalidCsvValueException::class);
        CsvParser::parseToInvoiceLines($stream);
    }

    /**
     * @throws InvalidCsvHeaderException
     * @throws InvalidCsvValueException
     */
    public function testParseManyLines(): void
    {
        $stream = CsvParserTest::stringToStream(
            "Customer,Vat number,Document number,Type,Parent document,Currency,Total\n".
            "Vendor 1,123456789,1000000257,1,,USD,400\n".
            "Vendor 2,987654321,1000000258,1,,EUR,900\n".
            "Vendor 3,123465123,1000000259,1,,GBP,1300"
        );

        $invoiceLines = CsvParser::parseToInvoiceLines($stream);
        $this->assertCount(3, $invoiceLines);
        $this->assertEquals("Vendor 1", $invoiceLines[0]->customer);
        $this->assertEquals("Vendor 2", $invoiceLines[1]->customer);
        $this->assertEquals("Vendor 3", $invoiceLines[2]->customer);
    }

    /**
     * @throws InvalidCsvValueException
     */
    public function testValidateHeader(): void
    {
        $stream = CsvParserTest::stringToStream(
            "Customeeerr,Vat number,Document number,Type,Parent document,Currency,Total\n".
            "Vendor 1,123456789,1000000257,1,,USD,400"
        );

        $this->expectException(InvalidCsvHeaderException::class);
        CsvParser::parseToInvoiceLines($stream);
    }

    private static function stringToStream(string $str)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $str);
        rewind($stream);
        return $stream;
    }
}
