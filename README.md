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
3. Pass the request to `SingleClient::send()` or `MultiClient::sendAll()`.
4. Read the returned `Response` object.

You may pass either a plain `Request` or the object returned by `Request::prepare()` to client `send*()` methods. When a plain `Request` is passed, the client prepares it internally with default `CurlOptions`.

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
    ->userAgent('MyApp/1.0')
    ->captureBody()
    ->captureHeaders();

$client = new SingleClient();

// With custom CurlOptions.
$response = $client->send($request->prepare($options));

// With default CurlOptions.
$response = $client->send($request);

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

`MultiClient::sendAll()` executes multiple requests with cURL multi and returns a `Responses` collection keyed by each request ID.

```php
<?php

use Ennacx\SimpleCurl\Client\MultiClient;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\Request;

$options = CurlOptions::create()
    ->timeout(10)
    ->followRedirects();

$phpRequest = Request::get('https://www.php.net/');
$packagistRequest = Request::get('https://packagist.org/');

$php = $phpRequest
    ->prepare($options);

$packagist = $packagistRequest
    ->prepare($options);

$client = new MultiClient();

// With custom CurlOptions.
$responses = $client->sendAll($php, $packagist);

// With default CurlOptions.
$responses = $client->sendAll($phpRequest, $packagistRequest);

$phpResponse = $responses->get($phpRequest->getId());
$packagistResponse = $responses->get($packagistRequest->getId());

echo $phpResponse->statusCode;
echo $packagistResponse->statusCode;

foreach($responses as $requestId => $response){
    echo $requestId . ': ' . $response->statusCode . PHP_EOL;
}
```

## Request

`Request` describes the HTTP request itself. It owns the URL, HTTP method, request ID, and request headers. It does not execute cURL and does not own transport options.

```php
<?php

use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Enum\ContentType;

$request = Request::post('https://api.example.com/users')
    ->accept(ContentType::Json)
    ->headers([
        'Content-Type' => 'application/json',
    ]);
```

### Accept Header

Use `accept()` or `accepts()` to add an `Accept` header from common `ContentType` values, `MediaRange` values, or custom media type strings.

```php
<?php

use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Enum\ContentType;
use Ennacx\SimpleCurl\Enum\MediaRange;

$request = Request::get('https://api.example.com/users')
    ->accept(ContentType::Json);

$request = Request::get('https://api.example.com/users')
    ->accepts(ContentType::Json, 'application/vnd.api+json');

$request = Request::get('https://api.example.com/users')
    ->accepts(
        ContentType::Json,
        ContentType::Html->withQuality(0.8),
        MediaRange::Any->withQuality(0.1),
    );
```

The third request sends:

```text
Accept: application/json, text/html;q=0.8, */*;q=0.1
```

Use `withQuality()` only for values that need an explicit Quality Value. You do not need to add it to every accepted type, and `q=1` can usually be omitted because it is the HTTP default.

If the same media type is added more than once, the first value wins. For example:

```php
$request = Request::get('https://api.example.com/users')
    ->accepts(
        ContentType::Json,
        ContentType::Json->withQuality(0.5),
    );
```

The request above sends only:

```text
Accept: application/json
```

You can still set a fully custom `Accept` header with `headers()`:

```php
$request = Request::get('https://api.example.com/users')
    ->headers([
        'Accept' => 'application/json;q=1.0, text/html;q=0.8',
    ]);
```

An explicitly provided `Accept` header is kept and is not overwritten by `accept()` or `accepts()`.

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
        'page'  => 1,
        'limit' => 20,
        'sort'  => 'name',
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

### Request Body

`Request` can also hold a request body. The body is converted into `CURLOPT_POSTFIELDS` when the request is executed.

`ContentType` represents common HTTP media types. In the current request body API, it is used to choose the default `Content-Type` header for `body()` and `bodyFromFile()`.

Use `body()` when you already have a raw string payload:

```php
<?php

use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Enum\ContentType;

$request = Request::post('https://api.example.com/messages')
    ->body('plain text message', ContentType::PlainText);
```

Use `bodyFromFile()` when the request body should be read from a local file:

```php
$request = Request::put('https://api.example.com/documents/1')
    ->bodyFromFile(__DIR__ . '/payload.txt', ContentType::PlainText);
```

`bodyFromFile()` reads the file contents and sends them as the request body. It is useful for APIs that expect raw text, JSON, XML, or binary-like payloads in the body. It is not a multipart file upload helper.

Use `json()` to encode an array as JSON. The default `Content-Type` is `application/json`.

```php
$request = Request::post('https://api.example.com/users')
    ->accept(ContentType::Json)
    ->json([
        'name'  => 'Taro',
        'email' => 'taro@example.com',
    ]);
```

You can also pass a pre-encoded JSON string. The string is validated before it is stored as the request body.

```php
$request = Request::post('https://api.example.com/users')
    ->json('{"name":"Taro","email":"taro@example.com"}');
```

Use `form()` for `application/x-www-form-urlencoded` payloads:

```php
$request = Request::post('https://api.example.com/token')
    ->form([
        'grant_type' => 'client_credentials',
        'client_id'  => 'example-client',
    ]);
```

When a body helper is used, PHP Simple cURL sets the matching `Content-Type` automatically unless you explicitly provide one with `headers()`.

```php
$request = Request::post('https://api.example.com/users')
    ->headers([
        'Content-Type' => 'application/vnd.api+json',
    ])
    ->json([
        'name' => 'Taro',
    ]);
```

Use `attach()` to send files as `multipart/form-data`:

```php
<?php

use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Entity\RequestAttachment;

$request = Request::post('https://api.example.com/upload')
    ->form([
        'description' => 'Profile image',
    ])
    ->attach(new RequestAttachment(
        name: 'file',
        path: __DIR__ . '/avatar.png',
        filename: 'avatar.png',
        mimeType: 'image/png',
    ));
```

When attachments are present, `Content-Type` is managed by cURL. Any user-defined `Content-Type` header is removed so cURL can generate the required multipart boundary.

If an attachment name conflicts with a form field name or another attachment, the attachment overwrites the existing field by default. Pass `allowOverwrite: false` to `attach()` to reject duplicate names instead:

```php
$request = Request::post('https://api.example.com/upload')
    ->form(['file' => 'already used'])
    ->attach(new RequestAttachment('file', __DIR__ . '/avatar.png'), allowOverwrite: false);
```

Use `attachFile()` when the default filename and MIME handling are enough:

```php
$request = Request::post('https://api.example.com/upload')
    ->attachFile('file', __DIR__ . '/avatar.png');
```

Attachments can be combined with `form()` fields only. JSON or raw body payloads cannot be mixed with file attachments.

Stream-based request bodies are planned for a later implementation. They are intentionally not part of the current public request body API yet.

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

$options = CurlOptions::create(
    AuthConfig::bearer('token'),
    SslConfig::verified(),
    ProxyConfig::http('proxy.example.com', port: 3128),
    TimeoutConfig::seconds(timeoutSec: 15, connectTimeoutSec: 5),
    RedirectConfig::enabled(maxRedirects: 5),
);
```

Fluent helpers are also available:

```php
$options = CurlOptions::create()
    ->timeout(10)
    ->followRedirects()
    ->userAgent('MyApp/1.0')
    ->referer('https://example.com')
    ->captureBody()
    ->captureHeaders();
```

Because options are immutable, this does not change the original instance:

```php
$baseOptions     = CurlOptions::create()->timeout(10);
$redirectOptions = $baseOptions->followRedirects();
```

## Sending Requests

Clients accept either `Request` or `PreparedRequest`.

Use `Request::prepare()` when you want to attach custom `CurlOptions` explicitly:

```php
$response = $client->send($request->prepare($options));
```

If no custom options are required, pass `Request` directly. The client converts it to `PreparedRequest` internally with default `CurlOptions`.

```php
$response  = $singleClient->send($request);
$responses = $multiClient->sendAll($requestA, $requestB);
```

`sendAll()` returns a `Responses` collection. Use `get()` when the response must exist, or `find()` when a missing response should return `null`.

`Responses` implements `ArrayAccess`, so you can also read responses with `$responses[$request->getId()]`.
The collection is read-only; write and unset operations throw `ImmutableCollectionException`.

```php
$response = $responses->get($requestA->getId());
$sameResponse = $responses[$requestA->getId()]; // array-style

$maybeResponse = $responses->find($requestB->getId());

foreach($responses as $requestId => $response){
    echo $response->statusCode;
}
```

## Config Objects

Config objects own their own cURL option mapping. The client passes them through `CurlOptionsFactory` before execution.

### Client

Use client settings to add `User-Agent` and `Referer` headers through `CurlOptions`.

```php
$options = CurlOptions::create()
    ->userAgent('MyApp/1.0')
    ->referer('https://example.com');
```

If the same headers are explicitly set with `Request::headers()`, those request headers are kept.

```php
$request = Request::get('https://api.example.com')
    ->headers([
        'User-Agent' => 'CustomAgent/2.0',
    ]);

$options = CurlOptions::create()
    ->userAgent('MyApp/1.0');
```

### SSL

```php
use Ennacx\SimpleCurl\Entity\Config\SslConfig;

$ssl = SslConfig::verified();
$insecure = SslConfig::insecure();
```

### Proxy

```php
use Ennacx\SimpleCurl\Entity\Config\ProxyConfig;

$httpProxy  = ProxyConfig::http('proxy.example.com', port: 3128);
$socksProxy = ProxyConfig::socks5('127.0.0.1', port: 1080);
```

### Authentication

```php
use Ennacx\SimpleCurl\Entity\Config\AuthConfig;

$basic  = AuthConfig::basic('user', 'password');
$bearer = AuthConfig::bearer('token');
```

### Timeout

```php
use Ennacx\SimpleCurl\Entity\Config\TimeoutConfig;

$timeout   = TimeoutConfig::seconds(timeoutSec: 10, connectTimeoutSec: 3);
$timeoutMs = TimeoutConfig::milliseconds(timeoutMs: 1500, connectTimeoutMs: 500);
```

### Redirects

```php
use Ennacx\SimpleCurl\Entity\Config\RedirectConfig;

$redirect   = RedirectConfig::enabled(maxRedirects: 10, autoReferer: true);
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

## Exceptions

PHP Simple cURL throws library-specific exceptions for invalid API usage and execution setup failures.

Network-level cURL errors such as connection failures or timeouts are returned as `Response::$error` and `Response::$errorMessage` when a response object can be created. They are not thrown by default.

```php
<?php

use Ennacx\SimpleCurl\Exception\SimpleCurlErrorInterface;

try{
    $response = $client->send($request);
} catch(SimpleCurlErrorInterface $e){
    echo $e->getMessage();
}
```

Exception classes:

- `InvalidRequestException` is thrown when the request itself is invalid, such as an invalid URL, header, query parameter, or `Accept` value.
- `RequestBodyException` is thrown when the request body cannot be built, such as invalid JSON, unreadable body files, invalid attachments, or unsupported body and attachment combinations.
- `InvalidConfigurationException` is thrown when cURL options or config objects are invalid.
- `CurlExecutionException` is thrown when cURL cannot be initialized or the cURL multi execution loop fails.
- `InvalidResponseException` is thrown when response data cannot be interpreted, such as JSON decode failure from `Response::json()`.
- `ResponseNotFoundException` is thrown when a response is not found in a `Responses` collection.
- `ImmutableCollectionException` is thrown when an immutable collection is modified.

## Notes

- `captureBody` controls whether the response body is stored in `Response::$body`.
- `captureHeaders` controls whether response header lines are available through `Response::rawHeaders()` and `Response::headers()`.
- Internally, `CURLOPT_RETURNTRANSFER` is enabled when either body or headers need to be captured.
- `MultiClient::sendAll()` returns a `Responses` collection, keyed by `Request::getId()`.

## License

[MIT](https://en.wikipedia.org/wiki/MIT_License)
