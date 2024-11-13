<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

/**
 * HTTPステータスコード
 */
class HttpStatusCode {

	public const OK                  = 200;
	public const CREATED             = 201;
	public const ACCEPTED            = 202;
	public const NO_CONTENT          = 204;
	public const BAD_REQUEST         = 400;
	public const UNAUTHORIZED        = 401;
	public const FORBIDDEN           = 403;
	public const NOT_FOUND           = 404;
	public const SERVER_ERROR        = 500;
	public const BAD_GATEWAY         = 502;
	public const SERVICE_UNAVAILABLE = 503;
	public const GATEWAY_TIMEOUT     = 504;
}