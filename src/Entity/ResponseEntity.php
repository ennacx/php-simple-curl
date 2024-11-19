<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Enum\CurlError;

/**
 * exec()メソッド後のcURL各結果
 *
 * @property string|null content_type (subtract character-set)
 * @property string|null character_set
 * @property int|null    content_length (alias for 'download_content_length')
 * @property float|null  latency (alias for 'total_time')
 * @property int|null    http_status_code (alias for 'http_code')
 * @property int|null    upload_speed (alias for 'speed_upload')
 * @property int|null    download_speed (alias for 'speed_download')
 *
 * @property mixed url
 * @property mixed http_code
 * @property mixed header_size
 * @property mixed request_size
 * @property mixed filetime
 * @property mixed ssl_verify_result
 * @property mixed total_time
 * @property mixed namelookup_time
 * @property mixed connect_time
 * @property mixed pretransfer_time
 * @property mixed size_upload
 * @property mixed size_download
 * @property mixed speed_download
 * @property mixed speed_upload
 * @property mixed download_content_length
 * @property mixed upload_content_length
 * @property mixed starttransfer_time
 * @property mixed redirect_count
 * @property mixed redirect_time
 * @property mixed redirect_url
 * @property mixed primary_ip
 * @property mixed certinfo
 * @property mixed primary_port
 * @property mixed local_ip
 * @property mixed local_port
 * @property mixed http_version
 * @property mixed protocol
 * @property mixed ssl_verifyresult
 * @property mixed scheme
 * @property mixed appconnect_time_us
 * @property mixed connect_time_us
 * @property mixed namelookup_time_us
 * @property mixed pretransfer_time_us
 * @property mixed redirect_time_us
 * @property mixed starttransfer_time_us
 * @property mixed total_time_us
 * @property mixed effective_method
 * @property mixed capath
 * @property mixed cainfo
 *
 * @see https://www.php.net/manual/ja/function.curl-getinfo.php
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
            $value = $this->getFromInfoRaw($name);
        }

        return $value;
    }

    /**
     * curl_getinfo()の一括取得
     *
     * @return array|null
     */
    public function getInfo(): ?array {
        return $this->getFromInfoRaw(null);
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

        $temp = $this->getContentTypeRaw();

        if($temp === null)
            return null;

        $result = preg_match('/^(?<type>.+)\s*;.*/', $temp, $matches);

        if(!$result || !isset($matches['type']))
            return null;

        return trim(strtolower($matches['type']));
    }

    /**
     * Character-Set
     *
     * @return string|null
     */
    protected function _getCharacterSet(): ?string {

        $temp = $this->getContentTypeRaw();

        if($temp === null)
            return null;

        $result = preg_match('/.*;\s*charset\s*=\s*(?<charset>.+)\s*$/', $temp, $matches);

        if(!$result || !isset($matches['charset']))
            return null;

        return trim(strtolower($matches['charset']));
    }

    /**
     * Content-Length
     *
     * @return int|null
     */
    protected function _getContentLength(): ?int {

        $temp = (isset($this->responseBody)) ? strlen($this->responseBody) : $this->getFromInfoRaw('size_download');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * cURLでのリクエスト～レスポンスまでの時間 (sec)
     *
     * @return float|null
     */
    protected function _getLatency(): ?float {

        $temp = $this->getFromInfoRaw('total_time');

        return ($temp !== null) ? floatval($temp) : null;
    }

    /**
     * HTTPステータスコード
     *
     * @return int|null
     */
    protected function _getHttpStatusCode(): ?int {

        $temp = $this->getFromInfoRaw('http_code');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * アップロード速度 (byte/sec)
     *
     * @return int|null
     */
    protected function _getUploadSpeed(): ?int {

        $temp = $this->getFromInfoRaw('speed_upload');

        return ($temp !== null) ? intval($temp) : null;
    }

    /**
     * ダウンロード速度 (byte/sec)
     *
     * @return int|null
     */
    protected function _getDownloadSpeed(): ?int {

        $temp = $this->getFromInfoRaw('speed_download');

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
    private function getFromInfoRaw(?string $key): mixed {

        // 未実行時や指定キーが存在しない場合は無視
        if($this->_infoRaw === null || ($key !== null && !array_key_exists($key, $this->_infoRaw)))
            return null;

        // キーにnullを指定した場合は全取得
        return ($key !== null) ? $this->_infoRaw[$key] : $this->_infoRaw;
    }

    /**
     * Content-Type (Raw)
     *
     * @return string|null
     */
    private function getContentTypeRaw(): ?string {

        $temp = $this->getFromInfoRaw('content_type');

        return ($temp !== null) ? $temp : null;
    }
}