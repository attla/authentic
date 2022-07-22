# Authentic user authentication layer

<p align="center">
<a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-lightgrey.svg" alt="License"></a>
<a href="https://packagist.org/packages/attla/authentic"><img src="https://img.shields.io/packagist/v/attla/authentic" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/attla/authentic"><img src="https://img.shields.io/packagist/dt/attla/authentic" alt="Total Downloads"></a>
</p>

🆔 An effective serverless user authentication layer.

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

NOTE: This package needs to be used with eloquent provider driver.

## License

This package is licensed under the [MIT license](LICENSE) © [Octha](https://octha.com).
