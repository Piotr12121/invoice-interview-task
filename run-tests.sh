#!/bin/bash

# Run tests inside Docker container
echo "Running PHPUnit tests..."
docker compose exec app php artisan test

echo "Running specific test suites..."
echo "Feature tests:"
docker compose exec app vendor/bin/phpunit tests/Feature

echo "Unit tests:"
docker compose exec app vendor/bin/phpunit tests/Unit