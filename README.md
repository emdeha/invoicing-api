# Invoicing API

This service processes a CSV with invoice data and returns the sum for each
invoice.

## Setup

1. [Install php](https://www.php.net/manual/en/install.php);
2. [Install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos);
3. Run `php composer update` to install dependencies;
4. `make test` to check that everything is setup correctly and the tests pass;
5. `make run` to run the service;
6. Use the following `curl` to test whether a request is being processed:

```
$ curl -F 'csvFile=@tests/data/sample.csv' \
    -F "currencyData=@tests/data/requestBody.json;type=application/json" \
   http://localhost:1337/api/invoices/calculate
```

7. (optional) Install [phpdbg](https://www.php.net/manual/en/intro.phpdbg.php) to run the test coverage checks.

## Useful commands

* `make lint` to run the linter and fix errors;
* `make run` to run the app in development mode;
* `make test` to run the tests;
* `make cover` to extract coverage statistics. The generated html coverage is
located at `tests/coverage-report`.
