# PHP Simple cURL

[![PHP Version Require](https://poser.pugx.org/ennacx/php-simple-curl/require/php)](https://packagist.org/packages/ennacx/php-simple-curl)
[![Latest Stable Version](https://poser.pugx.org/ennacx/php-simple-curl/v)](https://packagist.org/packages/ennacx/php-simple-curl)
[![Total Downloads](https://poser.pugx.org/ennacx/php-simple-curl/downloads)](https://packagist.org/packages/ennacx/php-simple-curl)
[![Latest Unstable Version](https://poser.pugx.org/ennacx/php-simple-curl/v/unstable)](https://packagist.org/packages/ennacx/php-simple-curl)
[![License](https://poser.pugx.org/ennacx/php-simple-curl/license)](https://packagist.org/packages/ennacx/php-simple-curl)

A small PHP 8.2+ cURL wrapper that builds typed request objects, executes them through single or multi clients, and returns response objects.

## Philosophy

PHP's cURL extension is powerful, but its option-based API can become hard to read as requests grow.
Additionally, the sheer number of granular cURL constants provided by PHP makes it challenging to select the most appropriate ones.

PHP Simple cURL keeps the core pieces explicit:

- `Request` describes what to send.
- `CurlOptions` describes how to send it.
- `PendingRequest` combines them into something executable.
- Clients execute requests and return typed `Response` objects.

The library favors small value objects, immutable options, and predictable response helpers over a large fluent client with hidden state.

## Requirements

- PHP 8.2 or later
- `ext-curl`
- `ext-openssl`
- Composer 2.x

## Installation

```bash
composer require ennacx/php-simple-curl:^2.0@beta
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

if($response->isSuccessful()){
    echo $response->header('content-type');
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

### Query Parameters

`Request` can manage GET query parameters separately from the base URL.

Existing query strings are parsed when the request is created:

```php
$request = Request::get('https://api.example.com/users?page=1')
    ->param('q', 'php curl')
    ->param('page', 2);
```

The request above is executed as:

```text
https://api.example.com/users?page=2&q=php+curl
```

Use `params()` to add multiple query parameters:

```php
$request = Request::get('https://api.example.com/users')
    ->params([
        'page' => 1,
        'limit' => 20,
        'sort' => 'name',
    ]);
```

Passing `null` removes an existing query parameter when overwriting is enabled:

```php
$request = Request::get('https://api.example.com/users?page=1&debug=1')
    ->param('debug', null);
```

Use `overwrite: false` to keep existing values:

```php
$request = Request::get('https://api.example.com/users?page=1')
    ->param('page', 2, overwrite: false)
    ->param('limit', 20);
```

URL fragments are preserved and appended after the rebuilt query string:

```php
$request = Request::get('https://example.com/docs?lang=en#install')
    ->param('version', '2.x');
```

This is executed as:

```text
https://example.com/docs?lang=en&version=2.x#install
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

`CurlOptions` is immutable. Fluent helper methods return a new instance, so remember to keep the returned value.

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

Because options are immutable, this does not change the original instance:

```php
$baseOptions = CurlOptions::create()->timeout(10);
$redirectOptions = $baseOptions->followRedirects();
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
print_r($response->headers);     // raw response header lines
print_r($response->headers());   // parsed response headers
print_r($response->info);        // curl_getinfo() result

if($response->isOk()){
    // HTTP 200 and no cURL error.
}

if($response->isSuccessful()){
    // HTTP 2xx and no cURL error.
}

if($response->isRedirect()){
    // HTTP 3xx.
}

if($response->isError()){
    // cURL error, HTTP 4xx, or HTTP 5xx.
}

if($response->hasHeader('content-type')){
    echo $response->header('content-type');
}

$json = $response->json();

if($response->error !== null){
    echo $response->error->name;
    echo $response->errorMessage;
}
```

Response status helpers:

- `isInformational()` returns true for HTTP `1xx`.
- `isOk()` returns true for HTTP `200` with no cURL error.
- `isSuccessful()` returns true for HTTP `2xx` with no cURL error.
- `isRedirect()` returns true for HTTP `3xx`.
- `isClientError()` returns true for HTTP `4xx`.
- `isServerError()` returns true for HTTP `5xx`.
- `isError()` returns true for a cURL error, HTTP `4xx`, or HTTP `5xx`.

Header helpers:

- `$response->headers` contains raw header lines.
- `$response->headers()` returns parsed headers keyed by lower-case header name.
- `$response->header('content-type')` returns a header value, an array of values, or `null`.
- `$response->hasHeader('content-type')` checks whether the header exists.

JSON helper:

- `$response->json()` decodes `Response::$body` as JSON.
- `$response->json(throw: false)` returns `null` when the body is empty.

## Notes

- `captureBody` controls whether the response body is stored in `Response::$body`.
- `captureHeaders` controls whether response header lines are stored in `Response::$headers`.
- Internally, `CURLOPT_RETURNTRANSFER` is enabled when either body or headers need to be captured.
- `MultiClient::sendAll()` returns `array<string, Response>`, keyed by `Request::$id`.

## License

[MIT](https://en.wikipedia.org/wiki/MIT_License)
