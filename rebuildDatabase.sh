#!/bin/bash

php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load

read -p "Wcisnij Enter"