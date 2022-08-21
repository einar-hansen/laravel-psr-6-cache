# Laravel PSR-6 Cache

[![Latest Version on Packagist](https://img.shields.io/packagist/v/einar-hansen/laravel-psr-6-cache.svg)](https://packagist.org/packages/einar-hansen/laravel-psr-6-cache)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/einar-hansen/laravel-psr-6-cache.svg)](https://packagist.org/packages/einar-hansen/laravel-psr-6-cache)
[![Total Downloads](https://img.shields.io/packagist/dt/einar-hansen/laravel-psr-6-cache.svg)](https://packagist.org/packages/einar-hansen/laravel-psr-6-cache)

This package adds PSR-6 cache support to Laravel 5.8 and above. Laravel 6 has PSR-6 support build in which can be used through the `cache.psr6` container alias. You should install symfony cache if you go down this route.

```bash
composer require symfony/cache
```

## Usage

To start using a `Psr\Cache\CacheItemPoolInterface` typed implementation that stores data in Laravel's configured cache, add this to a service provider:

```php
use EinarHansen\Cache\CacheItemPool;
use Illuminate\Contracts\Cache\Repository;
use Psr\Cache\CacheItemPoolInterface;

$this->app->singleton(CacheItemPoolInterface::class, function ($app) {
    return new CacheItemPool($app->make(Repository::class));
});
```

Right now you're all set to start injecting `CacheItemPoolInterface`'d everywhere you need it.

## Install

In order to install it via composer you should run this command:

```bash
composer require einar-hansen/laravel-psr-6-cache
```

## Testing
```bash
# Install packages
docker run --rm \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php80-composer:latest \
    composer install

# Run code style formatting and static analysis
docker run -it -v $(pwd):/app -w /app php:8.0-alpine vendor/bin/pint src
docker run -it -v $(pwd):/app -w /app php:8.0-alpine vendor/bin/phpstan --level=9 analyse
```

## Credits
This package is based on the package [madewithlove/illuminate-psr-cache-bridge](https://github.com/madewithlove/illuminate-psr-cache-bridge). It's modified to suit my preferences and is requiring PHP8.0.

- [https://github.com/madewithlove](https://github.com/madewithlove/illuminate-psr-cache-bridge)

## About
Einar Hansen is a developer based in Oslo, Norway. You'll find more information about me [on my website](https://einarhansen.dev).

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
