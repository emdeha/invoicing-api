lint:
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix -vvv bin/main.php

run:
	php -S localhost:1337 bin/main.php
