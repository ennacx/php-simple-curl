<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Request;

/**
 * File attachment for multipart/form-data requests.
 */
final readonly class RequestAttachment {

    /**
     * Creates a file attachment.
     *
     * @param string      $name     Multipart field name.
     * @param string      $path     Local file path.
     * @param string|null $filename Posted filename. Null lets cURL use the local filename.
     * @param string|null $mimeType MIME type. Null lets cURL infer or omit it.
     */
    public function __construct(
        public string $name,
        public string $path,
        public ?string $filename = null,
        public ?string $mimeType = null,
    ){
    }
}
