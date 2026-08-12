# zahls.ch PHP SDK

PHP library for the [zahls.ch](https://www.zahls.ch) payment API.

## Requirements

- PHP 8.0+
- ext-curl

## Install

```bash
composer require zahls/zahls-php
```

Until the package is on Packagist, add the GitHub repository:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/zahls/zahls-php.git"
    }
  ],
  "require": {
    "zahls/zahls-php": "dev-main"
  }
}
```

## Usage

```php
$zahls = new \Zahls\Zahls($instanceName, $apiSecret);

$gateway = new \Zahls\Models\Request\Gateway();
$gateway->setAmount(1000); // in cents
$gateway->setCurrency('CHF');
$gateway->setSuccessRedirectUrl('https://www.example.com/success');
$gateway->setFailedRedirectUrl('https://www.example.com/failed');
$gateway->setCancelRedirectUrl('https://www.example.com/cancel');

$response = $zahls->create($gateway);
```

The default API base domain is `zahls.ch` (`https://api.zahls.ch/...`).

Instance name is the subdomain of your account, e.g. `example` for `https://example.zahls.ch`.

## Examples

See the `examples/` directory.

## License

MIT
