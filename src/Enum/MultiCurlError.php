<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * マルチcURLエラー番号
 *
 * @see https://curl.se/libcurl/c/libcurl-errors.html
 */
enum MultiCurlError: int {

    case CALL_MULTI_PERFORM = -1;
    case OK = 0;
    case BAD_HANDLE = 1;
    case BAD_EASY_HANDLE = 2;
    case OUT_OF_MEMORY  = 3;
    case INTERNAL_ERROR  = 4;
    case BAD_SOCKET  = 5;
    case UNKNOWN_OPTION  = 6;
    case ADDED_ALREADY  = 7;
    case RECURSIVE_API_CALL = 8;
    case WAKEUP_FAILURE = 9;
    case BAD_FUNCTION_ARGUMENT = 10;
    case ABORTED_BY_CALLBACK = 11;
    case UNRECOVERABLE_POLL = 12;
}
