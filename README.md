# Authentic stateless user layer

<p align="center">
<a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-lightgrey.svg" alt="License"></a>
<a href="https://packagist.org/packages/attla/authentic"><img src="https://img.shields.io/packagist/v/attla/authentic" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/attla/authentic"><img src="https://img.shields.io/packagist/dt/attla/authentic" alt="Total Downloads"></a>
</p>

🆔 An effective serverless user authentication and authorization layer.

## Installation

```bash
composer require attla/authentic
```

## Configuration

In `./config/auth.php` on `guards` add the following array:

```php
'guards' => [
    // ...

    'authentic' => [
        'driver'   => 'authentic',
        'provider' => 'users',
    ],
],
```

If you want to configure the authentic as your default authentication guard set on `defaults.guard` as:

```php
'defaults' => [
    'guard' => 'authentic',
    // ...
],
```

Your model needs implements `Authenticatable` contract:

```php
use Illuminate\Contracts\Auth\Authenticatable;

class Example implements Authenticatable
{
    // ...
}
```

## Providers

Also in `./config/auth.php` on `guards` add the following array:

```php
'providers' => [
    // ...

    'dynamodb' => [
        'driver' => 'dynamodb',
        'model'  => Model::class,
        // 'gsi'    => '',
    ],
],
```

## License

This package is licensed under the [MIT license](LICENSE) © [Zunq](https://zunq.com).
