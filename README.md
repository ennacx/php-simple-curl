# PHP Simple cURL

[![PHP Version Require](https://poser.pugx.org/ennacx/php-simple-curl/require/php)](https://packagist.org/packages/ennacx/php-simple-curl)
[![Latest Stable Version](https://poser.pugx.org/ennacx/php-simple-curl/v)](https://packagist.org/packages/ennacx/php-simple-curl)
[![Total Downloads](https://poser.pugx.org/ennacx/php-simple-curl/downloads)](https://packagist.org/packages/ennacx/php-simple-curl)
[![Latest Unstable Version](https://poser.pugx.org/ennacx/php-simple-curl/v/unstable)](https://packagist.org/packages/ennacx/php-simple-curl)
[![License](https://poser.pugx.org/ennacx/php-simple-curl/license)](https://packagist.org/packages/ennacx/php-simple-curl)

A small PHP 8.2+ cURL wrapper that builds typed request objects, executes them through single or multi clients, and returns response objects.

## Requirements

- PHP 8.2 or later
- `ext-curl`
- `ext-openssl`
- Composer 2.x

## Installation

```bash
composer require ennacx/php-simple-curl
```

## Core Flow

1. Create a `Request` with the HTTP method, URL, and request headers.
2. Create `CurlOptions` for timeout, SSL, proxy, auth, redirect, and response capture settings.
3. Combine them into a `PendingRequest`.
4. Pass the pending request to `SingleClient::send()` or `MultiClient::sendAll()`.
5. Read the returned `Response` object.

## Single Request

```php
<?php

use Ennacx\SimpleCurl\Client\SingleClient;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\Request;

$request = Request::get('https://www.php.net/')
    ->headers([
        'Accept' => 'text/html',
    ]);

$options = CurlOptions::create()
    ->timeout(10)
    ->followRedirects()
    ->captureBody()
    ->captureHeaders();

$pendingRequest = $request->withOptions($options);

$client = new SingleClient();
$response = $client->send($pendingRequest);

echo $response->statusCode;
echo $response->body;

foreach($response->headers as $headerLine){
    echo $headerLine . PHP_EOL;
}

if($response->error !== null){
    echo $response->error->name;
    echo $response->errorMessage;
}
```

## Multiple Requests

`MultiClient::sendAll()` executes multiple pending requests with cURL multi and returns responses keyed by each request ID.

```php
<?php

use Ennacx\SimpleCurl\Client\MultiClient;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\Request;

$options = CurlOptions::create()
    ->timeout(10)
    ->followRedirects();

$php = Request::get('https://www.php.net/')
    ->withOptions($options);

$packagist = Request::get('https://packagist.org/')
    ->withOptions($options);

$client = new MultiClient();
$responses = $client->sendAll($php, $packagist);

$phpResponse = $responses[$php->request->id];
$packagistResponse = $responses[$packagist->request->id];

echo $phpResponse->statusCode;
echo $packagistResponse->statusCode;
```

## Request

`Request` describes the HTTP request itself. It owns the URL, HTTP method, request ID, and request headers. It does not execute cURL and does not own transport options.

```php
<?php

use Ennacx\SimpleCurl\Entity\Request;

$request = Request::post('https://api.example.com/users')
    ->headers([
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]);
```

Supported request factory methods:

- `Request::get()`
- `Request::post()`
- `Request::put()`
- `Request::delete()`
- `Request::patch()`
- `Request::head()`
- `Request::options()`

## Curl Options

`CurlOptions` describes how cURL should execute the request. It owns timeout, SSL, proxy, auth, redirect, and response capture settings.

```php
<?php

use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\Config\AuthConfig;
use Ennacx\SimpleCurl\Entity\Config\ProxyConfig;
use Ennacx\SimpleCurl\Entity\Config\RedirectConfig;
use Ennacx\SimpleCurl\Entity\Config\SslConfig;
use Ennacx\SimpleCurl\Entity\Config\TimeoutConfig;

$options = new CurlOptions(
    captureBody: true,
    captureHeaders: true,
    proxy: ProxyConfig::http('proxy.example.com', port: 3128),
    ssl: SslConfig::verified(),
    auth: AuthConfig::bearer('token'),
    timeout: TimeoutConfig::seconds(timeoutSec: 15, connectTimeoutSec: 5),
    redirect: RedirectConfig::enabled(maxRedirects: 5),
);
```

For simple usage, fluent helpers are available:

```php
$options = CurlOptions::create()
    ->timeout(10)
    ->followRedirects()
    ->captureBody()
    ->captureHeaders();
```

## Pending Request

`PendingRequest` combines a `Request` and optional `CurlOptions`. Clients receive this object.

```php
$pendingRequest = $request->withOptions($options);

// If no custom options are required, default CurlOptions are used internally.
$pendingRequest = $request->asPending();
```

## Config Objects

Config objects own their own cURL option mapping. The client passes them through `CurlOptionsFactory` before execution.

### Timeout

```php
use Ennacx\SimpleCurl\Entity\Config\TimeoutConfig;

$timeout = TimeoutConfig::seconds(timeoutSec: 10, connectTimeoutSec: 3);
$timeoutMs = TimeoutConfig::milliseconds(timeoutMs: 1500, connectTimeoutMs: 500);
```

### SSL

```php
use Ennacx\SimpleCurl\Entity\Config\SslConfig;

$ssl = SslConfig::verified();
$insecure = SslConfig::insecure();
```

### Authentication

```php
use Ennacx\SimpleCurl\Entity\Config\AuthConfig;

$basic = AuthConfig::basic('user', 'password');
$bearer = AuthConfig::bearer('token');
```

### Proxy

```php
use Ennacx\SimpleCurl\Entity\Config\ProxyConfig;

$httpProxy = ProxyConfig::http('proxy.example.com', port: 3128);
$socksProxy = ProxyConfig::socks5('127.0.0.1', port: 1080);
```

### Redirects

```php
use Ennacx\SimpleCurl\Entity\Config\RedirectConfig;

$redirect = RedirectConfig::enabled(maxRedirects: 10, autoReferer: true);
$noRedirect = RedirectConfig::disabled();
```

## Response

Both clients return `Response` objects.

```php
echo $response->statusCode;      // int
echo $response->body;            // string|null
print_r($response->headers);     // string[]
print_r($response->info);        // curl_getinfo() result

if($response->error !== null){
    echo $response->error->name;
    echo $response->errorMessage;
}
```

## Notes

- `captureBody` controls whether the response body is stored in `Response::$body`.
- `captureHeaders` controls whether response headers are stored in `Response::$headers`.
- Internally, `CURLOPT_RETURNTRANSFER` is enabled when either body or headers need to be captured.
- `MultiClient::sendAll()` returns `array<string, Response>`, keyed by `Request::$id`.

## License

[MIT](https://en.wikipedia.org/wiki/MIT_License)
