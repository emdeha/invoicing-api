<?php

declare(strict_types=1);

namespace InvoicingAPI\Invoice;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;

final class ViewTest extends TestCase
{
    use ProphecyTrait;

    private $view;

    public function testHandleRequest(): void
    {
        $request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $csvFile = "Customer,Vat number,Document number,Type,Parent document,Currency,Total\n".
            "Vendor 1,123456789,1000000257,1,,USD,400";
        $jsonData = <<<EOD
{
    "exchangeRates": {
        "EUR": 1,
        "USD": 0.987,
        "GBP": 0.878
    },
    "outputCurrency": "GBP"
}
EOD;
        $request->getUploadedFiles()->willReturn(
            ViewTest::uploadedFilesToStream($csvFile, $jsonData)
        );

        $response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $stream = $this->prophesize('Psr\Http\Message\StreamInterface');
        $response->getBody()->willReturn($stream)->shouldBeCalled();
        $response->withHeader('Content-Type', 'application/json')->shouldBeCalled();

        $this->view->sumInvoicesHandler($request->reveal(), $response->reveal(), []);
    }

    public function testMissingExchangeRate(): void
    {
        $request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $csvFile = "Customer,Vat number,Document number,Type,Parent document,Currency,Total\n".
            "Vendor 1,123456789,1000000257,1,,USD,400";
        $jsonData = <<<EOD
{
    "outputCurrency": "GBP"
}
EOD;
        $request->getUploadedFiles()->willReturn(
            ViewTest::uploadedFilesToStream($csvFile, $jsonData)
        );

        $response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $stream = $this->prophesize('Psr\Http\Message\StreamInterface');
        $response
            ->withStatus(400)
            ->willReturn($response->reveal())
            ->shouldBeCalled();
        $response
            ->withHeader('content-type', 'application/json')
            ->willReturn($response);
        $response
            ->getBody()
            ->willReturn($stream);

        $this->view->sumInvoicesHandler($request->reveal(), $response->reveal(), []);
    }

    public function testMissingOutputCurrency(): void
    {
        $request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $csvFile = "Customer,Vat number,Document number,Type,Parent document,Currency,Total\n".
            "Vendor 1,123456789,1000000257,1,,USD,400";
        $jsonData = <<<EOD
{
    "exchangeRates": {
        "EUR": 1,
        "USD": 0.987,
        "GBP": 0.878
    }
}
EOD;
        $request->getUploadedFiles()->willReturn(
            ViewTest::uploadedFilesToStream($csvFile, $jsonData)
        );

        $response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $stream = $this->prophesize('Psr\Http\Message\StreamInterface');
        $response
            ->withStatus(400)
            ->willReturn($response->reveal())
            ->shouldBeCalled();
        $response
            ->withHeader('content-type', 'application/json')
            ->willReturn($response);
        $response
            ->getBody()
            ->willReturn($stream);

        $this->view->sumInvoicesHandler($request->reveal(), $response->reveal(), []);
    }

    private static function uploadedFilesToStream(string $csvData, string $jsonData)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csvData);
        rewind($stream);

        return [
            'csvFile' => new class ($stream) {
                private $stream;

                public function __construct($stream)
                {
                    $this->stream = $stream;
                }

                public function getStream()
                {
                    return new class ($this->stream) {
                        private $stream;

                        public function __construct($stream)
                        {
                            $this->stream = $stream;
                        }

                        public function detach()
                        {
                            return $this->stream;
                        }
                    };
                }
            },
            'currencyData' => new class ($jsonData) {
                private $jsonData;

                public function __construct($jsonData)
                {
                    $this->jsonData = $jsonData;
                }

                public function getStream()
                {
                    return new class ($this->jsonData) {
                        private $jsonData;

                        public function __construct($jsonData)
                        {
                            $this->jsonData = $jsonData;
                        }

                        public function __toString()
                        {
                            return $this->jsonData;
                        }
                    };
                }
            }
        ];
    }

    protected function setUp(): void
    {
        $useCaseReturn = <<<EOD
[{
    "customer": "Vendor 1",
    "documentSums": [{"documentNumber":"1000000257","sum":385.76}]
}]
EOD;

        $handler = $this->prophesize('Psr\Http\Server\RequestHandlerInterface');
        $useCase = $this->prophesize(SumInvoices\UseCase::class);

        $useCase
            ->do(Argument::cetera())
            ->willReturn($useCaseReturn);

        $this->view = new View($handler->reveal(), $useCase->reveal());
    }
}
