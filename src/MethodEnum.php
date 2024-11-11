<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

enum MethodEnum {

    /** HTTP GETモード */
    case GET;

    /** HTTP POSTモード */
    case POST;

    /** HTTP PUTモード */
    case PUT;

    /** HTTP DELETEモード */
    case DELETE;
}
