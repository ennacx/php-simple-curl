<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * HTTPステータスコード
 */
enum HttpStatusCode: int implements FromValueImpl {

    case OK                  = 200;
    case CREATED             = 201;
    case ACCEPTED            = 202;
    case NO_CONTENT          = 204;
    case BAD_REQUEST         = 400;
    case UNAUTHORIZED        = 401;
    case FORBIDDEN           = 403;
    case NOT_FOUND           = 404;
    case SERVER_ERROR        = 500;
    case BAD_GATEWAY         = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT     = 504;

    public static function fromValue(int $value): self {
        return self::from($value);
    }

    public function getKeyword(): string {

        return match($this){
            self::OK                  => 'OK',
            self::CREATED             => 'Created',
            self::ACCEPTED            => 'Accepted',
            self::NO_CONTENT          => 'No content',
            self::BAD_REQUEST         => 'Bad request',
            self::UNAUTHORIZED        => 'Unauthorized',
            self::FORBIDDEN           => 'Forbidden',
            self::NOT_FOUND           => 'Not found',
            self::SERVER_ERROR        => 'Internal server error',
            self::BAD_GATEWAY         => 'Bad gateway',
            self::SERVICE_UNAVAILABLE => 'Service unavailable',
            self::GATEWAY_TIMEOUT     => 'Gateway timeout'
        };
    }
}