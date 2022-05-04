# Invoicing API

This service processes a CSV with invoice data and returns the sum for each
invoice.

## Setup

1. [Install php](https://www.php.net/manual/en/install.php);
2. [Install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos);
3. Install `docker` and `docker-compose`
4. Run `php composer update` to install dependencies;
5. `make test` to check that everything is setup correctly and the tests pass;
6. `make run` to run the service;
7. Use the following `curl` to test whether a request is being processed:

```
$ curl -F 'csvFile=@tests/data/sample.csv' \
    -F "currencyData=@tests/data/requestBody.json;type=application/json" \
   http://localhost:8000/api/invoices/calculate
```

7. (optional) Install [phpdbg](https://www.php.net/manual/en/intro.phpdbg.php) to run the test coverage checks.

## Useful commands

* `make lint` to run the linter and fix errors;
* `make run` to run the app in development mode;
* `make test` to run the tests;
* `make cover` to extract coverage statistics. The generated html coverage is
located at `tests/coverage-report`.

## Tracing

The app is configured to support tracing. It uses [Jaeger](https://www.jaegertracing.io/) as a tracing frontend and `opentracing/opentracing` as a tracing backend.

You can see the traces for each request at `http://localhost:16686`.
