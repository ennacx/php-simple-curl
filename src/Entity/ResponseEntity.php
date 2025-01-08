<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Enum\CurlError;
use InvalidArgumentException;
use Stringable;

/**
 * exec()メソッド後のcURL各結果
 *
 * @property string|null content_type (subtract character-set)
 * @property string|null character_set
 * @property int|null    content_length (alias for 'download_content_length')
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
class ResponseEntity extends AbstEntity implements Stringable {

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

    /** @var TimeEntity 時間に関するエンティティー */
    public TimeEntity $time;

    /** @var array|null curl_getinfo()の生データ */
    private ?array $_infoRaw = null;

    /**
     * cURLレスポンス内容を使いやすくした実体クラス
     */
    public function __construct(){

        // TimeEntity初期化
        $this->time = new TimeEntity();
    }

    /**
     * エンティティーが保持しているダイナミックプロパティの他にcURLの情報から取得出来れば取得
     *
     * @param  string $name
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function &__get(string $name): mixed {

        $value = parent::__get($name);
        if($value === null)
            $value = $this->getFromInfoRaw($name);

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
     * 所要時間に関する内容をセット
     *
     * @return void
     */
    public function setTime(): void {

        if(isset($this->_infoRaw)){
            // 秒
            $temp = $this->getFromInfoRaw('total_time');
            $this->time->total = ($temp !== null) ? floatval($temp) : null;
            $temp = $this->getFromInfoRaw('namelookup_time');
            $this->time->nsLookup = ($temp !== null) ? floatval($temp) : null;
            $temp = $this->getFromInfoRaw('appconnect_time');
            $this->time->appConnect = ($temp !== null) ? floatval($temp) : null;
            $temp = $this->getFromInfoRaw('connect_time');
            $this->time->connect = ($temp !== null) ? floatval($temp) : null;
            $temp = $this->getFromInfoRaw('pretransfer_time');
            $this->time->preTransfer = ($temp !== null) ? floatval($temp) : null;
            $temp = $this->getFromInfoRaw('starttransfer_time');
            $this->time->startTransfer = ($temp !== null) ? floatval($temp) : null;
            $temp = $this->getFromInfoRaw('redirect_time');
            $this->time->redirect = ($temp !== null) ? floatval($temp) : null;

            // マイクロ秒
            $temp = $this->getFromInfoRaw('total_time_us');
            $this->time->total_us = ($temp !== null) ? intval($temp) : null;
            $temp = $this->getFromInfoRaw('namelookup_time_us');
            $this->time->nsLookup_us = ($temp !== null) ? intval($temp) : null;
            $temp = $this->getFromInfoRaw('appconnect_time_us');
            $this->time->appConnect_us = ($temp !== null) ? intval($temp) : null;
            $temp = $this->getFromInfoRaw('connect_time_us');
            $this->time->connect_us = ($temp !== null) ? intval($temp) : null;
            $temp = $this->getFromInfoRaw('pretransfer_time_us');
            $this->time->preTransfer_us = ($temp !== null) ? intval($temp) : null;
            $temp = $this->getFromInfoRaw('starttransfer_time_us');
            $this->time->startTransfer_us = ($temp !== null) ? intval($temp) : null;
            $temp = $this->getFromInfoRaw('redirect_time_us');
            $this->time->redirect_us = ($temp !== null) ? intval($temp) : null;

            unset($temp);
        }
    }

    /**
     * 文字列変換
     *
     * @return string
     */
    public function __toString(): string {
        return $this->responseBody ?? '';
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