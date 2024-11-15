<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * HTTPステータスコード
 *
 * @see https://developer.mozilla.org/ja/docs/Web/HTTP/Status
 */
enum HttpStatusCode: int implements FromValueImpl {

    case CONTINUE              = 100;
    case SW_PROTOCOLS          = 101;
    case OK                    = 200;
    case CREATED               = 201;
    case ACCEPTED              = 202;
    case NO_CONTENT            = 204;
    case RESET_CONTENT         = 205;
    case PARTIAL_CONTENT       = 206;
    case MULTI_CHOICES         = 300;
    case MOVED_PERMANENTLY     = 301;
    case FOUND                 = 302;
    case SEE_OTHER             = 303;
    case NOT_MODIFIED          = 304;
    case TEMPORARY_REDIRECT    = 307;
    case PERMANENTLY_REDIRECT  = 308;
    case BAD_REQUEST           = 400;
    case UNAUTHORIZED          = 401;
    case FORBIDDEN             = 403;
    case NOT_FOUND             = 404;
    case METHOD_NOT_ALLOWED    = 405;
    case NOT_ACCEPTABLE        = 406;
    case PROXY_AUTH_REQUIRED   = 407;
    case REQUEST_TIMEOUT       = 408;
    case CONFLICT              = 409;
    case GONE                  = 410;
    case LENGTH_REQUIRED       = 411;
    case PRECONDITION_FAILED   = 412;
    case PAYLOAD_TOO_LARGE     = 413;
    case URI_TOO_LONG          = 414;
    case UNSUPPORTED_MEDIA     = 415;
    case RANGE_NOT_SATISFY     = 416;
    case EXPECTATION_FAILED    = 417;
    case I_AM_A_TEAPOT         = 418;
    case MISDIRECTED_REQUEST   = 421;
    case TOO_EARLY             = 425;
    case UPGRADE_REQUIRED      = 426;
    case PRECOND_REQUIRED      = 428;
    case TOO_MANY_REQUESTS     = 429;
    case REQ_HEADER_TOO_LARGE  = 431;
    case UNAVAILABLE_FOR_LEGAL = 451;
    case SERVER_ERROR          = 500;
    case BAD_GATEWAY           = 502;
    case SERVICE_UNAVAILABLE   = 503;
    case GATEWAY_TIMEOUT       = 504;
    case HTTP_VER_NOT_SUPPORT  = 505;
    case VARIANT_NEGOTIATES    = 506;
    case NOT_EXTENDED          = 510;
    case NETWORK_AUTH_REQUIRED = 511;

    public static function fromValue(int $value): self {
        return self::from($value);
    }

    public function getKeyword(): string {

        return match($this){
            self::CONTINUE              => 'Continue',
            self::SW_PROTOCOLS          => 'Switching Protocols',
            self::OK                    => 'OK',
            self::CREATED               => 'Created',
            self::ACCEPTED              => 'Accepted',
            self::NO_CONTENT            => 'No content',
            self::RESET_CONTENT         => 'Reset content',
            self::PARTIAL_CONTENT       => 'Partial content',
            self::MULTI_CHOICES         => 'Multiple choices',
            self::MOVED_PERMANENTLY     => 'Moved Permanently',
            self::FOUND                 => 'Found',
            self::SEE_OTHER             => 'See Other',
            self::NOT_MODIFIED          => 'Not modified',
            self::TEMPORARY_REDIRECT    => 'Temporary redirect',
            self::PERMANENTLY_REDIRECT  => 'Permanent redirect',
            self::BAD_REQUEST           => 'Bad request',
            self::UNAUTHORIZED          => 'Unauthorized',
            self::FORBIDDEN             => 'Forbidden',
            self::NOT_FOUND             => 'Not found',
            self::METHOD_NOT_ALLOWED    => 'Method not allowed',
            self::NOT_ACCEPTABLE        => 'Not acceptable',
            self::PROXY_AUTH_REQUIRED   => 'Proxy authentication required',
            self::REQUEST_TIMEOUT       => 'Request timeout',
            self::CONFLICT              => 'Conflict',
            self::GONE                  => 'Gone',
            self::LENGTH_REQUIRED       => 'Length required',
            self::PRECONDITION_FAILED   => 'Precondition failed',
            self::PAYLOAD_TOO_LARGE     => 'Payload too large',
            self::URI_TOO_LONG          => 'URI too long',
            self::UNSUPPORTED_MEDIA     => 'Unsupported media type',
            self::RANGE_NOT_SATISFY     => 'Range not satisfiable',
            self::EXPECTATION_FAILED    => 'Expectation failed',
            self::I_AM_A_TEAPOT         => 'I\'m a teapot',
            self::MISDIRECTED_REQUEST   => 'Misdirected request',
            self::TOO_EARLY             => 'Too early',
            self::UPGRADE_REQUIRED      => 'Upgrade required',
            self::PRECOND_REQUIRED      => 'Precondition required',
            self::TOO_MANY_REQUESTS     => 'Too many requests',
            self::REQ_HEADER_TOO_LARGE  => 'Request header too large',
            self::UNAVAILABLE_FOR_LEGAL => 'Unavailable for legal reasons',
            self::SERVER_ERROR          => 'Internal server error',
            self::BAD_GATEWAY           => 'Bad gateway',
            self::SERVICE_UNAVAILABLE   => 'Service unavailable',
            self::GATEWAY_TIMEOUT       => 'Gateway timeout',
            self::HTTP_VER_NOT_SUPPORT  => 'HTTP version not supported',
            self::VARIANT_NEGOTIATES    => 'Variant also negotiates',
            self::NOT_EXTENDED          => 'Not extended',
            self::NETWORK_AUTH_REQUIRED => 'Network authentication required'
        };
    }
}