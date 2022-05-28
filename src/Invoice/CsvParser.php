<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice;

use Exception;

define("CUSTOMER_COLUMN", 0);
define("VAT_NUMBER_COLUMN", 1);
define("DOCUMENT_NUMBER_COLUMN", 2);
define("TYPE_COLUMN", 3);
define("PARENT_DOCUMENT_COLUMN", 4);
define("CURRENCY_COLUMN", 5);
define("TOTAL_COLUMN", 6);

class CsvParser
{
    /**
     * @throws InvalidCsvHeaderException
     * @throws InvalidCsvValueException
     */
    public static function parseToInvoiceLines($csvStream): array
    {
        $invoiceLines = [];
        $row = 0;

        while (($line = fgetcsv($csvStream)) !== false) {
            $row++;

            if ($row === 1) {
                CsvParser::validateCsvHeader($line);
                continue;
            }

            if (!is_numeric($line[TYPE_COLUMN])) {
                throw new InvalidCsvValueException();
            }
            if (!is_numeric($line[TOTAL_COLUMN])) {
                throw new InvalidCsvValueException();
            }

            $invoiceLines[] = new SumInvoices\InvoiceLine(
                $line[CUSTOMER_COLUMN],
                $line[VAT_NUMBER_COLUMN],
                $line[DOCUMENT_NUMBER_COLUMN],
                intval($line[TYPE_COLUMN]),
                $line[PARENT_DOCUMENT_COLUMN],
                $line[CURRENCY_COLUMN],
                intval($line[TOTAL_COLUMN])
            );
        }

        return $invoiceLines;
    }

    /**
     * @throws InvalidCsvHeaderException
     */
    private static function validateCsvHeader($headerCells): void
    {
        if ($headerCells[CUSTOMER_COLUMN] !== "Customer") {
            throw new InvalidCsvHeaderException();
        }
        if ($headerCells[VAT_NUMBER_COLUMN] !== "Vat number") {
            throw new InvalidCsvHeaderException();
        }
        if ($headerCells[DOCUMENT_NUMBER_COLUMN] !== "Document number") {
            throw new InvalidCsvHeaderException();
        }
        if ($headerCells[TYPE_COLUMN] !== "Type") {
            throw new InvalidCsvHeaderException();
        }
        if ($headerCells[PARENT_DOCUMENT_COLUMN] !== "Parent document") {
            throw new InvalidCsvHeaderException();
        }
        if ($headerCells[CURRENCY_COLUMN] !== "Currency") {
            throw new InvalidCsvHeaderException();
        }
        if ($headerCells[TOTAL_COLUMN] !== "Total") {
            throw new InvalidCsvHeaderException();
        }
    }
}

class InvalidCsvValueException extends Exception
{
}

class InvalidCsvHeaderException extends Exception
{
}
