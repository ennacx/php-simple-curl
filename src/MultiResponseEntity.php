<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

class MultiResponseEntity {

    public string $id;
    public string $url;
    public bool $result;
    public ?string $responseHeader = null;
    public ?string $responseBody = null;
    public ?CurlError $errorEnum = null;
    public ?string $errorMessage = null;
}