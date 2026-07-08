<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Enum\CurlError;

/**
 * cURL実行後のレスポンス情報を保持する値オブジェクト。
 */
final readonly class Response {

    /**
     * コンストラクタ
     *
     * @param int            $statusCode   HTTPステータスコード
     * @param string[]       $headers      レスポンスヘッダー行
     * @param string|null    $body         レスポンスボディ
     * @param array          $info         curl_getinfo()の結果
     * @param CurlError|null $error        cURLエラー。成功時はnull
     * @param string         $errorMessage cURLエラーメッセージ
     */
    public function __construct(
        public int        $statusCode,
        public array      $headers,
        public ?string    $body,
        public array      $info,
        public ?CurlError $error        = null,
        public string     $errorMessage = '',
    ){}
}
