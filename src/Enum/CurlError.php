<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * cURL easy error codes.
 *
 * Case names follow current libcurl CURLcode names where possible. Some values
 * are kept with deprecated names for PHP/libcurl compatibility and are marked.
 *
 * @see https://curl.se/libcurl/c/libcurl-errors.html
 * @see https://curl.se/libcurl/c/symbols-in-versions.html
 * @see https://github.com/curl/curl/blob/master/include/curl/curl.h
 */
enum CurlError: int {

    /**
     * No error.
     *
     * Added in libcurl 7.1.
     */
    case OK = 0;

    /**
     * The URL protocol is not supported by this libcurl build.
     *
     * Added in libcurl 7.1.
     */
    case UNSUPPORTED_PROTOCOL = 1;

    /**
     * Early initialization failed.
     *
     * Added in libcurl 7.1.
     */
    case FAILED_INIT = 2;

    /**
     * The URL was not properly formatted.
     *
     * Added in libcurl 7.1.
     */
    case URL_MALFORMAT = 3;

    /**
     * A requested feature, protocol, or option was not built into libcurl.
     *
     * The value was previously exposed as `CURLE_URL_MALFORMAT_USER`.
     * That old symbol was added in libcurl 7.1 and obsoleted in 7.17.0.
     * The value was reused as `CURLE_NOT_BUILT_IN` in libcurl 7.21.5.
     */
    case NOT_BUILT_IN = 4;

    /**
     * The proxy host could not be resolved.
     *
     * Added in libcurl 7.1.
     */
    case COULDNT_RESOLVE_PROXY = 5;

    /**
     * The remote host could not be resolved.
     *
     * Added in libcurl 7.1.
     */
    case COULDNT_RESOLVE_HOST = 6;

    /**
     * Failed to connect to the host or proxy.
     *
     * Added in libcurl 7.1.
     */
    case COULDNT_CONNECT = 7;

    /**
     * The server returned data that libcurl could not parse.
     *
     * Added as `CURLE_WEIRD_SERVER_REPLY` in libcurl 7.51.0.
     * The same value was known as `CURLE_FTP_WEIRD_SERVER_REPLY` from
     * libcurl 7.1 until it was renamed in 7.51.0.
     */
    case WEIRD_SERVER_REPLY = 8;

    /**
     * Access to the remote resource was denied.
     *
     * Added as `CURLE_REMOTE_ACCESS_DENIED` in libcurl 7.17.0.
     * Older libcurl exposed the FTP-specific name `CURLE_FTP_ACCESS_DENIED`
     * from libcurl 7.1 until 7.17.0.
     */
    case REMOTE_ACCESS_DENIED = 9;

    /**
     * FTP active-mode accept failed.
     *
     * Added as `CURLE_FTP_ACCEPT_FAILED` in libcurl 7.24.0.
     * This value had been obsolete since libcurl 7.15.4 and was reused in
     * 7.24.0.
     */
    case FTP_ACCEPT_FAILED = 10;

    /**
     * FTP password command returned an unexpected reply.
     *
     * Added in libcurl 7.1.
     */
    case FTP_WEIRD_PASS_REPLY = 11;

    /**
     * FTP active-mode accept timed out.
     *
     * Added as `CURLE_FTP_ACCEPT_TIMEOUT` in libcurl 7.24.0.
     * This value had been obsolete since libcurl 7.17.0 and was reused in
     * 7.24.0.
     */
    case FTP_ACCEPT_TIMEOUT = 12;

    /**
     * FTP PASV/EPSV reply could not be parsed.
     *
     * Added in libcurl 7.1.
     */
    case FTP_WEIRD_PASV_REPLY = 13;

    /**
     * FTP 227 response had an invalid format.
     *
     * Added in libcurl 7.1.
     */
    case FTP_WEIRD_227_FORMAT = 14;

    /**
     * FTP could not resolve the host used for a new connection.
     *
     * Added in libcurl 7.1.
     */
    case FTP_CANT_GET_HOST = 15;

    /**
     * HTTP/2 framing layer error.
     *
     * Added as `CURLE_HTTP2` in libcurl 7.38.0.
     * This value had been obsolete since libcurl 7.17.0 and was reused in
     * 7.38.0.
     */
    case HTTP2 = 16;

    /**
     * FTP transfer type could not be set.
     *
     * Added as `CURLE_FTP_COULDNT_SET_TYPE` in libcurl 7.17.0.
     */
    case FTP_COULDNT_SET_TYPE = 17;

    /**
     * A file transfer was shorter or larger than expected.
     *
     * Added in libcurl 7.1.
     */
    case PARTIAL_FILE = 18;

    /**
     * FTP RETR command failed.
     *
     * Added in libcurl 7.1.
     */
    case FTP_COULDNT_RETR_FILE = 19;

    /* Value 20 is obsolete in modern libcurl. */

    /**
     * A quote command failed.
     *
     * Added as `CURLE_QUOTE_ERROR` in libcurl 7.17.0.
     */
    case QUOTE_ERROR = 21;

    /**
     * HTTP returned an error when `CURLOPT_FAILONERROR` is enabled.
     *
     * Added as `CURLE_HTTP_RETURNED_ERROR` in libcurl 7.10.3.
     * The older `CURLE_HTTP_NOT_FOUND` symbol existed from libcurl 7.1 until
     * 7.10.3.
     */
    case HTTP_RETURNED_ERROR = 22;

    /**
     * A write callback or local write operation failed.
     *
     * Added in libcurl 7.1.
     */
    case WRITE_ERROR = 23;

    /* Value 24 is obsolete in modern libcurl. */

    /**
     * Upload failed.
     *
     * Added as `CURLE_UPLOAD_FAILED` in libcurl 7.16.3.
     */
    case UPLOAD_FAILED = 25;

    /**
     * A local file could not be opened or read.
     *
     * Added in libcurl 7.1.
     */
    case READ_ERROR = 26;

    /**
     * Memory allocation failed.
     *
     * Added in libcurl 7.1.
     */
    case OUT_OF_MEMORY = 27;

    /**
     * The operation timed out.
     *
     * Added as `CURLE_OPERATION_TIMEDOUT` in libcurl 7.10.2.
     * The older `CURLE_OPERATION_TIMEOUTED` symbol existed from libcurl 7.1
     * until 7.17.0.
     */
    case OPERATION_TIMEDOUT = 28;

    /* Value 29 is obsolete in modern libcurl. */

    /**
     * FTP PORT command failed.
     *
     * Added in libcurl 7.1.
     */
    case FTP_PORT_FAILED = 30;

    /**
     * FTP REST command failed.
     *
     * Added in libcurl 7.1.
     */
    case FTP_COULDNT_USE_REST = 31;

    /* Value 32 is obsolete in modern libcurl. */

    /**
     * A range request could not be performed.
     *
     * Added as `CURLE_RANGE_ERROR` in libcurl 7.17.0.
     * The older `CURLE_HTTP_RANGE_ERROR` symbol existed from libcurl 7.1 until
     * 7.17.0.
     */
    case RANGE_ERROR = 33;

    /**
     * Obsolete HTTP POST error.
     *
     * Added as `CURLE_HTTP_POST_ERROR` in libcurl 7.1.
     * Obsoleted in libcurl 7.56.0 and now represented by `CURLE_OBSOLETE34`.
     *
     * @deprecated Not used since libcurl 7.56.0. It will be removed in version 3.0.0.
     */
    case HTTP_POST_ERROR = 34;

    /**
     * SSL/TLS connection failed.
     *
     * Added in libcurl 7.1.
     */
    case SSL_CONNECT_ERROR = 35;

    /**
     * Download could not be resumed.
     *
     * Added as `CURLE_BAD_DOWNLOAD_RESUME` in libcurl 7.10.
     */
    case BAD_DOWNLOAD_RESUME = 36;

    /**
     * A file:// URL could not be read.
     *
     * Added in libcurl 7.1.
     */
    case FILE_COULDNT_READ_FILE = 37;

    /**
     * LDAP bind failed.
     *
     * Added in libcurl 7.1.
     */
    case LDAP_CANNOT_BIND = 38;

    /**
     * LDAP search failed.
     *
     * Added in libcurl 7.1.
     */
    case LDAP_SEARCH_FAILED = 39;

    /* Value 40 is obsolete in modern libcurl. */

    /**
     * Obsolete function lookup error.
     *
     * Added as `CURLE_FUNCTION_NOT_FOUND` in libcurl 7.1.
     * Obsoleted in libcurl 7.53.0 and now represented by `CURLE_OBSOLETE41`.
     *
     * @deprecated Not used since libcurl 7.53.0. It will be removed in version 3.0.0.
     */
    case FUNCTION_NOT_FOUND = 41;

    /**
     * Operation was aborted by a callback.
     *
     * Added in libcurl 7.1.
     */
    case ABORTED_BY_CALLBACK = 42;

    /**
     * A function was called with a bad argument.
     *
     * Added in libcurl 7.1.
     */
    case BAD_FUNCTION_ARGUMENT = 43;

    /* Value 44 is obsolete in modern libcurl. */

    /**
     * `CURLOPT_INTERFACE` could not use the specified interface.
     *
     * Added as `CURLE_INTERFACE_FAILED` in libcurl 7.12.0.
     * The older `CURLE_HTTP_PORT_FAILED` symbol existed from libcurl 7.3 until
     * 7.12.0.
     */
    case INTERFACE_FAILED = 45;

    /* Value 46 is obsolete in modern libcurl. */

    /**
     * Too many redirects were followed.
     *
     * Added in libcurl 7.5.
     */
    case TOO_MANY_REDIRECTS = 47;

    /**
     * An unknown option was passed to libcurl.
     *
     * Added as `CURLE_UNKNOWN_OPTION` in libcurl 7.21.5.
     * The older `CURLE_UNKNOWN_TELNET_OPTION` symbol existed from libcurl 7.7
     * until 7.21.5 and maps to this value.
     */
    case UNKNOWN_OPTION = 48;

    /**
     * A setopt option was badly formatted.
     *
     * Added as `CURLE_SETOPT_OPTION_SYNTAX` in libcurl 7.78.0.
     * The older `CURLE_TELNET_OPTION_SYNTAX` symbol existed from libcurl 7.7
     * until 7.78.0 and maps to this value.
     */
    case SETOPT_OPTION_SYNTAX = 49;

    /* Values 50 and 51 are obsolete in modern libcurl. */

    /**
     * No data was returned by the server.
     *
     * Added in libcurl 7.9.1.
     */
    case GOT_NOTHING = 52;

    /**
     * The requested SSL crypto engine was not found.
     *
     * Added in libcurl 7.9.3.
     */
    case SSL_ENGINE_NOTFOUND = 53;

    /**
     * The SSL crypto engine could not be set as default.
     *
     * Added in libcurl 7.9.3.
     */
    case SSL_ENGINE_SETFAILED = 54;

    /**
     * Failed sending network data.
     *
     * Added in libcurl 7.10.
     */
    case SEND_ERROR = 55;

    /**
     * Failed receiving network data.
     *
     * Added in libcurl 7.10.
     */
    case RECV_ERROR = 56;

    /* Value 57 is obsolete in modern libcurl. */

    /**
     * Local client certificate problem.
     *
     * Added in libcurl 7.10.
     */
    case SSL_CERTPROBLEM = 58;

    /**
     * The specified SSL cipher could not be used.
     *
     * Added in libcurl 7.10.
     */
    case SSL_CIPHER = 59;

    /**
     * Peer certificate or SSH fingerprint verification failed.
     *
     * Added as `CURLE_PEER_FAILED_VERIFICATION` in libcurl 7.17.1.
     * The older `CURLE_SSL_PEER_CERTIFICATE` symbol existed from libcurl 7.8
     * until 7.17.1. `CURLE_SSL_CACERT` existed from libcurl 7.10 and was
     * unified with this value in libcurl 7.62.0.
     */
    case PEER_FAILED_VERIFICATION = 60;

    /**
     * Transfer encoding was invalid or unsupported.
     *
     * Added in libcurl 7.10.
     */
    case BAD_CONTENT_ENCODING = 61;

    /**
     * Obsolete LDAP invalid URL error.
     *
     * Added as `CURLE_LDAP_INVALID_URL` in libcurl 7.10.8.
     * Obsoleted in libcurl 7.82.0 and now represented by `CURLE_OBSOLETE62`.
     *
     * @deprecated Not used since libcurl 7.82.0. It will be removed in version 3.0.0.
     */
    case LDAP_INVALID_URL = 62;

    /**
     * Maximum file size was exceeded.
     *
     * Added in libcurl 7.10.8.
     */
    case FILESIZE_EXCEEDED = 63;

    /**
     * Requested FTP SSL level failed.
     *
     * Added as `CURLE_USE_SSL_FAILED` in libcurl 7.17.0.
     * The older `CURLE_FTP_SSL_FAILED` symbol existed from libcurl 7.11.0 until
     * 7.17.0.
     */
    case USE_SSL_FAILED = 64;

    /**
     * Sending data required a rewind that failed.
     *
     * Added in libcurl 7.12.3.
     */
    case SEND_FAIL_REWIND = 65;

    /**
     * SSL engine initialization failed.
     *
     * Added in libcurl 7.12.3.
     */
    case SSL_ENGINE_INITFAILED = 66;

    /**
     * Login credentials were not accepted.
     *
     * Added in libcurl 7.13.1.
     */
    case LOGIN_DENIED = 67;

    /**
     * TFTP file was not found.
     *
     * Added in libcurl 7.15.0.
     */
    case TFTP_NOTFOUND = 68;

    /**
     * TFTP permission problem.
     *
     * Added in libcurl 7.15.0.
     */
    case TFTP_PERM = 69;

    /**
     * Remote server ran out of disk space.
     *
     * Added as `CURLE_REMOTE_DISK_FULL` in libcurl 7.17.0.
     * The older `CURLE_TFTP_DISKFULL` symbol existed from libcurl 7.15.0 until
     * 7.17.0.
     */
    case REMOTE_DISK_FULL = 70;

    /**
     * Illegal TFTP operation.
     *
     * Added in libcurl 7.15.0.
     */
    case TFTP_ILLEGAL = 71;

    /**
     * Unknown TFTP transfer ID.
     *
     * Added in libcurl 7.15.0.
     */
    case TFTP_UNKNOWNID = 72;

    /**
     * Remote file already exists.
     *
     * Added as `CURLE_REMOTE_FILE_EXISTS` in libcurl 7.17.0.
     * The older `CURLE_TFTP_EXISTS` symbol existed from libcurl 7.15.0 until
     * 7.17.0.
     */
    case REMOTE_FILE_EXISTS = 73;

    /**
     * TFTP no such user.
     *
     * Added in libcurl 7.15.0.
     */
    case TFTP_NOSUCHUSER = 74;

    /**
     * Obsolete conversion failure.
     *
     * Added as `CURLE_CONV_FAILED` in libcurl 7.15.4.
     * Obsoleted in libcurl 7.82.0 and now represented by `CURLE_OBSOLETE75`.
     *
     * @deprecated Not used since libcurl 7.82.0. It will be removed in version 3.0.0.
     */
    case CONV_FAILED = 75;

    /**
     * Obsolete conversion-callback requirement.
     *
     * Added as `CURLE_CONV_REQD` in libcurl 7.15.4.
     * Obsoleted in libcurl 7.82.0 and now represented by `CURLE_OBSOLETE76`.
     *
     * @deprecated Not used since libcurl 7.82.0. It will be removed in version 3.0.0.
     */
    case CONV_REQD = 76;

    /**
     * CA certificate file could not be loaded.
     *
     * Added in libcurl 7.16.0.
     */
    case SSL_CACERT_BADFILE = 77;

    /**
     * Remote file was not found.
     *
     * Added in libcurl 7.16.1.
     */
    case REMOTE_FILE_NOT_FOUND = 78;

    /**
     * SSH layer error.
     *
     * Added in libcurl 7.16.1.
     */
    case SSH = 79;

    /**
     * SSL connection shutdown failed.
     *
     * Added in libcurl 7.16.1.
     */
    case SSL_SHUTDOWN_FAILED = 80;

    /**
     * Socket is not ready for send or receive.
     *
     * Added in libcurl 7.18.2.
     */
    case AGAIN = 81;

    /**
     * CRL file could not be loaded.
     *
     * Added in libcurl 7.19.0.
     */
    case SSL_CRL_BADFILE = 82;

    /**
     * Issuer check failed.
     *
     * Added in libcurl 7.19.0.
     */
    case SSL_ISSUER_ERROR = 83;

    /**
     * FTP PRET command failed.
     *
     * Added in libcurl 7.20.0.
     */
    case FTP_PRET_FAILED = 84;

    /**
     * RTSP CSeq numbers did not match.
     *
     * Added in libcurl 7.20.0.
     */
    case RTSP_CSEQ_ERROR = 85;

    /**
     * RTSP session IDs did not match.
     *
     * Added in libcurl 7.20.0.
     */
    case RTSP_SESSION_ERROR = 86;

    /**
     * FTP file list could not be parsed.
     *
     * Added in libcurl 7.21.0.
     */
    case FTP_BAD_FILE_LIST = 87;

    /**
     * Chunk callback reported an error.
     *
     * Added in libcurl 7.21.0.
     */
    case CHUNK_FAILED = 88;

    /**
     * No connection was available; the transfer was queued.
     *
     * Added in libcurl 7.30.0.
     */
    case NO_CONNECTION_AVAILABLE = 89;

    /**
     * Pinned public key did not match.
     *
     * Added as `CURLE_SSL_PINNEDPUBKEYNOTMATCH` in libcurl 7.39.0.
     */
    case SSL_PINNED_PUBKEY_NOT_MATCH = 90;

    /**
     * Certificate status verification failed.
     *
     * Added as `CURLE_SSL_INVALIDCERTSTATUS` in libcurl 7.41.0.
     */
    case SSL_INVALID_CERT_STATUS = 91;

    /**
     * HTTP/2 stream error.
     *
     * Added in libcurl 7.49.0.
     */
    case HTTP2_STREAM = 92;

    /**
     * libcurl API was called recursively from inside a callback.
     *
     * Added in libcurl 7.59.0.
     */
    case RECURSIVE_API_CALL = 93;

    /**
     * Authentication function returned an error.
     *
     * Added in libcurl 7.66.0.
     */
    case AUTH_ERROR = 94;

    /**
     * HTTP/3 layer error.
     *
     * Added in libcurl 7.68.0.
     */
    case HTTP3 = 95;

    /**
     * QUIC connection error.
     *
     * Added in libcurl 7.69.0.
     */
    case QUIC_CONNECT_ERROR = 96;

    /**
     * Proxy handshake error.
     *
     * Added in libcurl 7.73.0.
     */
    case PROXY = 97;

    /**
     * Client-side SSL certificate is required.
     *
     * Added as `CURLE_SSL_CLIENTCERT` in libcurl 7.77.0.
     */
    case SSL_CLIENT_CERT = 98;

    /**
     * poll/select returned an unrecoverable error.
     *
     * Added in libcurl 7.84.0.
     */
    case UNRECOVERABLE_POLL = 99;

    /**
     * A value or data field exceeded an allowed size.
     *
     * Added in libcurl 8.6.0.
     */
    case TOO_LARGE = 100;

    /**
     * Encrypted ClientHello was required but failed.
     *
     * Added in libcurl 8.8.0.
     */
    case ECH_REQUIRED = 101;

    /**
     * Unknown cURL easy error code returned by the local PHP/libcurl build.
     *
     * This is not a libcurl CURLcode. It is used by this library as a fallback
     * for newer or vendor-specific error numbers.
     */
    case OTHER = -1;
}
