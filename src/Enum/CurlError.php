<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * cURLエラー番号
 *
 * @see https://curl.se/libcurl/c/libcurl-errors.html
 */
enum CurlError: int {

    case OK = 0;
    case UNSUPPORTED_PROTOCOL = 1;
    case FAILED_INIT = 2;
    case URL_MALFORMAT  = 3;
    case URL_MALFORMAT_USER  = 4;
    case COULDNT_RESOLVE_PROXY  = 5;
    case COULDNT_RESOLVE_HOST  = 6;
    case COULDNT_CONNECT  = 7;
    case FTP_WEIRD_SERVER_REPLY = 8;
    case REMOTE_ACCESS_DENIED = 9;
    case FTP_ACCEPT_FAILED = 10;
    case FTP_WEIRD_PASS_REPLY = 11;
    case FTP_ACCEPT_TIMEOUT = 12;
    case FTP_WEIRD_PASV_REPLY = 13;
    case FTP_WEIRD_227_FORMAT = 14;
    case FTP_CANT_GET_HOST = 15;
    case HTTP2 = 16;
    case FTP_COULDNT_SET_TYPE = 17;
    case PARTIAL_FILE = 18;
    case FTP_COULDNT_RETR_FILE = 19;
    case QUOTE_ERROR = 21;
    case HTTP_RETURNED_ERROR = 22;
    case WRITE_ERROR = 23;
    case UPLOAD_FAILED = 25;
    case READ_ERROR = 26;
    case OUT_OF_MEMORY = 27;
    case OPERATION_TIMEDOUT = 28;
    case FTP_PORT_FAILED = 30;
    case FTP_COULDNT_USE_REST = 31;
    case RANGE_ERROR = 33;
    case HTTP_POST_ERROR = 34;
    case SSL_CONNECT_ERROR = 35;
    case BAD_DOWNLOAD_RESUME = 36;
    case FILE_COULDNT_READ_FILE = 37;
    case LDAP_CANNOT_BIND = 38;
    case LDAP_SEARCH_FAILED = 39;
    case FUNCTION_NOT_FOUND = 41;
    case ABORTED_BY_CALLBACK = 42;
    case BAD_FUNCTION_ARGUMENT = 43;
    case INTERFACE_FAILED = 45;
    case TOO_MANY_REDIRECTS = 47;
    case UNKNOWN_TELNET_OPTION = 48;
    case TELNET_OPTION_SYNTAX = 49;
    case GOT_NOTHING = 52;
    case SSL_ENGINE_NOTFOUND = 53;
    case SSL_ENGINE_SETFAILED = 54;
    case SEND_ERROR = 55;
    case RECV_ERROR = 56;
    case SSL_CERTPROBLEM = 58;
    case SSL_CIPHER = 59;
    case SSL_CACERT = 60;
    case BAD_CONTENT_ENCODING = 61;
    case LDAP_INVALID_URL = 62;
    case FILESIZE_EXCEEDED = 63;
    case USE_SSL_FAILED = 64;
    case SEND_FAIL_REWIND = 65;
    case SSL_ENGINE_INITFAILED = 66;
    case LOGIN_DENIED = 67;
    case TFTP_NOTFOUND = 68;
    case TFTP_PERM = 69;
    case REMOTE_DISK_FULL = 70;
    case TFTP_ILLEGAL = 71;
    case TFTP_UNKNOWNID = 72;
    case REMOTE_FILE_EXISTS = 73;
    case TFTP_NOSUCHUSER = 74;
    case CONV_FAILED = 75;
    case CONV_REQD = 76;
    case SSL_CACERT_BADFILE = 77;
    case REMOTE_FILE_NOT_FOUND = 78;
    case SSH = 79;
    case SSL_SHUTDOWN_FAILED = 80;
    case AGAIN = 81;
    case SSL_CRL_BADFILE = 82;
    case SSL_ISSUER_ERROR = 83;
    case FTP_PRET_FAILED = 84;
    case RTSP_CSEQ_ERROR = 85;
    case RTSP_SESSION_ERROR = 86;
    case FTP_BAD_FILE_LIST = 87;
    case CHUNK_FAILED = 88;
    case NO_CONNECTION_AVAILABLE = 89;
    case SSL_PINNED_PUBKEY_NOT_MATCH = 90;
    case SSL_INVALID_CERT_STATUS = 91;
    case HTTP2_STREAM = 92;
    case RECURSIVE_API_CALL = 93;
    case AUTH_ERROR = 94;
    case HTTP3 = 95;
    case QUIC_CONNECT_ERROR = 96;
    case PROXY = 97;
    case SSL_CLIENT_CERT = 98;
    case UNRECOVERABLE_POLL = 99;
    case TOO_LARGE = 100;
    case ECH_REQUIRED = 101;

    case OTHER = -1;
}
