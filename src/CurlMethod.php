<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

/**
 * HTTPメソッド
 */
enum CurlMethod {

    /** HTTP GETモード */
    case GET;

    /** HTTP POSTモード */
    case POST;

    /** HTTP PUTモード */
    case PUT;

    /** HTTP DELETEモード */
    case DELETE;
}
