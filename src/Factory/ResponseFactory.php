<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Factory;

use CurlHandle;
use Ennacx\SimpleCurl\Exception\CurlExecutionException;
use Ennacx\SimpleCurl\Exception\InvalidResponseException;
use Ennacx\SimpleCurl\Request\PreparedRequest;
use Ennacx\SimpleCurl\Response\Response;

/**
 * Builds Response objects from cURL execution results.
 */
final class ResponseFactory {

    /**
     * Creates a response from a cURL handle and raw transfer result.
     *
     * @param  CurlHandle      $ch              Executed cURL handle.
     * @param  bool|string     $raw             Result from curl_exec() or curl_multi_getcontent().
     * @param  PreparedRequest $preparedRequest Request and options used for execution.
     * @param  int|null        $resultCode      cURL result code from curl_multi_info_read().
     * @throws CurlExecutionException
     * @throws InvalidResponseException
     */
    public function fromCurlResult(CurlHandle $ch, bool|string $raw, PreparedRequest $preparedRequest, ?int $resultCode = null): Response {

        $info = curl_getinfo($ch);
        if(!is_array($info)){
            throw new CurlExecutionException('Invalid curl info');
        }

        $options = $preparedRequest->getOptions();
        $errno   = $resultCode ?? curl_errno($ch);

        // ヘッダーとボディを分割してそれぞれ格納
        $headers = [];
        $body    = null;
        if(is_string($raw)){
            // ヘッダーが必要な場合
            if($options->isCapturingHeaders()){
                // ヘッダーサイズ取得
                $headerSize = $info['header_size'] ?? null;
                if(!is_int($headerSize)){
                    throw new InvalidResponseException('Invalid cURL header size.');
                }

                // ヘッダーとボディを分割・格納
                $headers = $this->parseRawHeaders(substr($raw, 0, $headerSize));
                if($options->isCapturingBody()){
                    $body = substr($raw, $headerSize);
                }
            }
            // ボディのみの場合はそのまま
            else if($options->isCapturingBody()){
                $body = $raw;
            }
        }

        return new Response(
            statusCode:   $info['http_code'],
            headers:      $headers,
            body:         $body,
            info:         $info,
            error:        ($errno !== CURLE_OK) ? $errno : null,
            errorMessage: ($errno !== CURLE_OK) ? curl_error($ch) : '',
        );
    }

    /**
     * 生のレスポンスヘッダー文字列を行単位の配列へ分割する。
     *
     * cURLが返すステータスラインもヘッダー行として保持する。
     *
     * @param  string $rawHeaders cURLが返したレスポンスヘッダー文字列
     * @return string[]
     */
    private function parseRawHeaders(string $rawHeaders): array {

        $headers = [];
        foreach(preg_split('/\r\n|\r|\n/', trim($rawHeaders)) ?: [] as $line){
            if($line !== ''){
                $headers[] = $line;
            }
        }

        return $headers;
    }
}
