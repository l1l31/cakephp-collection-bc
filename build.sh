cp -rf /var/www/cakephp/src/Collection/* ./src
cp  -rf /var/www/cakephp/tests/TestCase/Collection ./tests

find ./src -name "*.php" -exec php "build/php-short-array-to-long.php" -w "{}" \;
find ./tests -name "*.php" -exec php "build/php-short-array-to-long.php" -w "{}" \;
