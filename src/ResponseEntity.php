<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

/**
 * exec()メソッド後のcURL各結果
 */
class ResponseEntity {

    /** @var string SimpleCurlLibのID */
    public string $id;

    /** @var string SimpleCurlLibで設定したURL */
    public string $url;

    /** @var boolean cURL成功可否 */
    public bool $result;

    /** @var string|null cURLレスポンスヘッダー */
    public ?string $responseHeader = null;

    /** @var string|null cURLレスポンスボディー */
    public ?string $responseBody = null;

    /** @var CurlError|null cURLエラーEnum */
    public ?CurlError $errorEnum = null;

    /** @var string|null cURLエラーメッセージ */
    public ?string $errorMessage = null;

    /** @var array|null curl_getinfo()の生データ */
    private ?array $_infoRaw = null;

    /**
     * Content-Type (Raw)
     *
     * @return string|null
     */
    public function getContentTypeRaw(): ?string {

        $temp = $this->_getFromInfoRaw('content_type');

        return ($temp !== null) ? $temp : null;
    }

    /**
     * Content-Type
     *
     * @return string|null
     */
    public function getContentType(): ?string {

        $temp = $this->getContentTypeRaw();

        if($temp === null){
            return null;
        }

        $result = preg_match('/^(?<type>.+)\s*;.*/', $temp, $matches);

        if(!$result || !isset($matches['type'])){
            return null;
        }

        return trim($matches['type']);
    }

    /**
     * Character-Set
     *
     * @return string|null
     */
    public function getCharacterSet(): ?string {

        $temp = $this->getContentTypeRaw();

        if($temp === null){
            return null;
        }

        $result = preg_match('/.*;\s*charset\s*=\s*(?<charset>.+)\s*$/', $temp, $matches);

        if(!$result || !isset($matches['charset'])){
            return null;
        }

        return trim($matches['charset']);
    }

    /**
     * cURLでのリクエスト～レスポンスまでの時間 (sec)
     *
     * @return float|null
     */
    public function getLatency(): ?float {

        $temp = $this->_getFromInfoRaw('total_time');

        return ($temp !== null) ? floatval($temp) : null;
    }

    /**
     * HTTPステータスコード
     *
     * @return int|null
     */
    public function getHttpStatusCode(): ?int {

        $temp = $this->_getFromInfoRaw('http_code');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * 実際のリダイレクト回数
     *
     * @return int|null
     */
    public function getRedirectCount(): ?int {

        $temp = $this->_getFromInfoRaw('redirect_count');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * Content-Length
     *
     * @return int|null
     */
    public function getContentSize(): ?int {

        $temp = $this->_getFromInfoRaw('size_download');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * アップロード速度 (byte/sec)
     *
     * @return int|null
     */
    public function getUploadSpeed(): ?int {

        $temp = $this->_getFromInfoRaw('speed_upload');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * ダウンロード速度 (byte/sec)
     *
     * @return int|null
     */
    public function getDownloadSpeed(): ?int {

        $temp = $this->_getFromInfoRaw('speed_download');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * curl_getinfo()の一括取得
     *
     * @return array|null
     */
    public function getInfo(): ?array {
        return $this->_getFromInfoRaw(null);
    }

    /**
     * curl_getinfo()の全内容をセット
     *
     * @param  array<string, mixed> $info
     * @return void
     */
    public function setInfo(array $info): void {
        $this->_infoRaw = $info;
    }

    /**
     * info配列からデータ取得
     *
     * @link https://www.php.net/manual/ja/function.curl-getinfo.php
     *
     * @param  string|null $key null時は全取得
     * @return mixed
     */
    private function _getFromInfoRaw(?string $key): mixed {

        // 未実行時や指定キーが存在しない場合は無視
        if($this->_infoRaw === null || ($key !== null && !array_key_exists($key, $this->_infoRaw))){
            return null;
        }

        // キーにnullを指定した場合は全取得
        return ($key !== null) ? $this->_infoRaw[$key] : $this->_infoRaw;
    }
}