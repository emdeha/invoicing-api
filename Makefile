lint:
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix -vvv bin
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix -vvv src
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix -vvv tests

run:
	docker compose build
	docker compose up

test:
	./vendor/bin/phpunit tests

cover:
	phpdbg -qrr ./vendor/bin/phpunit --coverage-html tests/coverage-report tests
