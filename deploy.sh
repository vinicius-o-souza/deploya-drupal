export PATH="/opt/alt/php84/usr/bin:$PATH"

git pull origin main

composer install

./vendor/bin/drush cim -y

./vendor/bin/drush cr
