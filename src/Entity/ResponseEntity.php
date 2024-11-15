<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Enum\CurlError;

/**
 * exec()メソッド後のcURL各結果
 *
 * @property mixed content_type
 * @property mixed character_set
 * @property mixed latency
 * @property mixed http_status_code
 * @property mixed redirect_count
 * @property mixed content_size
 * @property mixed upload_speed
 * @property mixed download_speed
 */
class ResponseEntity extends AbstEntity {

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
     * cURLレスポンス内容を使いやすくした実体クラス
     *
     * @param array $curlInfo
     */
    public function __construct(array $curlInfo = []){

        if(!empty($curlInfo)){
            $this->_infoRaw = $curlInfo;
        }
    }

    /**
     * エンティティーが保持しているダイナミックプロパティの他にcURLの情報から取得出来れば取得
     *
     * @param  string $name
     * @return mixed
     */
    public function &__get(string $name): mixed{

        $value = parent::__get($name);
        if($value === null){
            $value = $this->_getFromInfoRaw($name);
        }

        return $value;
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
     * Content-Type
     *
     * @return string|null
     */
    protected function _getContentType(): ?string {

        $temp = $this->_getContentTypeRaw();

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
    protected function _getCharacterSet(): ?string {

        $temp = $this->_getContentTypeRaw();

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
    protected function _getLatency(): ?float {

        $temp = $this->_getFromInfoRaw('total_time');

        return ($temp !== null) ? floatval($temp) : null;
    }

    /**
     * HTTPステータスコード
     *
     * @return int|null
     */
    protected function _getHttpStatusCode(): ?int {

        $temp = $this->_getFromInfoRaw('http_code');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * 実際のリダイレクト回数
     *
     * @return int|null
     */
    protected function _getRedirectCount(): ?int {

        $temp = $this->_getFromInfoRaw('redirect_count');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * Content-Length
     *
     * @return int|null
     */
    protected function _getContentSize(): ?int {

        $temp = $this->_getFromInfoRaw('size_download');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * アップロード速度 (byte/sec)
     *
     * @return int|null
     */
    protected function _getUploadSpeed(): ?int {

        $temp = $this->_getFromInfoRaw('speed_upload');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * ダウンロード速度 (byte/sec)
     *
     * @return int|null
     */
    protected function _getDownloadSpeed(): ?int {

        $temp = $this->_getFromInfoRaw('speed_download');

        return ($temp !== null) ? intval($temp) : null;
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

    /**
     * Content-Type (Raw)
     *
     * @return string|null
     */
    private function _getContentTypeRaw(): ?string {

        $temp = $this->_getFromInfoRaw('content_type');

        return ($temp !== null) ? $temp : null;
    }
}