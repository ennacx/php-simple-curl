<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

use Ennacx\SimpleCurl\Request\AcceptValueInterface;
use Ennacx\SimpleCurl\Request\QualifiedAcceptValue;

/**
 * Common HTTP media types.
 */
enum ContentType : string implements AcceptValueInterface {

    /** Plain text. */
    case PlainText = 'text/plain';

    /** HTML. */
    case Html = 'text/html';

    /** JSON. */
    case Json = 'application/json';

    /** XML. */
    case Xml = 'application/xml';

    /** URL-encoded form data. */
    case FormUrlEncoded = 'application/x-www-form-urlencoded';

    /** PDF. */
    case Pdf = 'application/pdf';

    /** Binary data. */
    case OctetStream = 'application/octet-stream';

    /** Multipart form data. */
    case MultipartFormData = 'multipart/form-data';

    /**
     * Returns the value as a Content-Type header.
     *
     * @param  boolean $returnArray Whether to return an associative header array.
     * @return array{'Content-Type': string}|string
     */
    public function getContentTypeHeader(bool $returnArray = true): array|string {
        return ($returnArray) ?
            ['Content-Type' => $this->value] :
            sprintf('Content-Type: %s', $this->value);
    }

    /**
     * Returns this media type with an Accept quality value.
     *
     * @param float $quality Quality value between 0.0 and 1.0.
     */
    public function withQuality(float $quality): QualifiedAcceptValue {
        return new QualifiedAcceptValue($this, $quality);
    }

    /**
     * @inheritDoc
     */
    public function toHeaderValue(): string {
        return $this->value;
    }
}
