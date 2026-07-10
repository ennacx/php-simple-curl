<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Factory;

use CurlHandle;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\ConfiguredRequest;
use Ennacx\SimpleCurl\Entity\Response;
use Ennacx\SimpleCurl\Enum\CurlError;
use InvalidArgumentException;
use RuntimeException;

/**
 * cURLの実行結果からResponseを組み立てるFactory。
 *
 * CurlOptionsのcapture設定に従って、raw文字列からレスポンスヘッダーとボディを切り分ける。
 */
final class ResponseFactory {

    /**
     * CurlHandleのメタ情報と実行結果からResponseを生成する。
     *
     * @param  CurlHandle        $ch             実行済みのcURLハンドラー
     * @param  bool|string       $raw            curl_exec()またはcurl_multi_getcontent()の戻り値
     * @param  ConfiguredRequest $configuredRequest 実行に使用した設定済みリクエスト
     * @param  int|null          $resultCode     `curl_multi_info_read()`のresult。単一実行時はnull
     * @return Response
     */
    public function fromCurlResult(CurlHandle $ch, bool|string $raw, ConfiguredRequest $configuredRequest, ?int $resultCode = null): Response {

        $info = curl_getinfo($ch);
        if(!is_array($info)){
            throw new RuntimeException('Invalid curl info');
        }

        $options      = $configuredRequest->options ?? CurlOptions::create();
        $errno        = $resultCode ?? curl_errno($ch);
        $error        = ($errno !== CURLE_OK) ? (CurlError::tryFrom($errno) ?? CurlError::OTHER) : null;
        $errorMessage = ($errno !== CURLE_OK) ? curl_error($ch) : '';

        // ヘッダーとボディを分割してそれぞれ格納
        $headers = [];
        $body    = null;
        if(is_string($raw)){
            // ヘッダーが必要な場合
            if($options->captureHeaders){
                // ヘッダーサイズ取得
                $headerSize = $info['header_size'] ?? null;
                if(!is_int($headerSize)){
                    throw new InvalidArgumentException('Invalid cURL header size.');
                }

                // ヘッダーとボディを分割・格納
                $headers = $this->parseHeaders(substr($raw, 0, $headerSize));
                if($options->captureBody){
                    $body = substr($raw, $headerSize);
                }
            }
            // ボディのみの場合はそのまま
            else if($options->captureBody){
                $body = $raw;
            }
        }

        return new Response(
            statusCode: $info['http_code'],
            headers: $headers,
            body: $body,
            info: $info,
            error: $error,
            errorMessage: $errorMessage,
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
    private function parseHeaders(string $rawHeaders): array {

        $headers = [];
        foreach(preg_split('/\r\n|\r|\n/', trim($rawHeaders)) ?: [] as $line){
            if($line !== ''){
                $headers[] = $line;
            }
        }

        return $headers;
    }
}
