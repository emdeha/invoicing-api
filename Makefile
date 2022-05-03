lint:
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix -vvv bin
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix -vvv src
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix -vvv tests

run:
	php -S localhost:1337 bin/main.php

test:
	./vendor/bin/phpunit tests
