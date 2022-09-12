#!/bin/sh
if [ -f ./vendor/bin/phpunit ]; then
	./vendor/bin/phpunit t --testdox tests
else
	phpunit t --testdox tests
fi

