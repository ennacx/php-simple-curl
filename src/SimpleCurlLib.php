<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

use CurlHandle;
use Ennacx\SimpleCurl\Entity\ResponseEntity;
use Ennacx\SimpleCurl\Enum\CurlAuth;
use Ennacx\SimpleCurl\Enum\CurlError;
use Ennacx\SimpleCurl\Enum\CurlMethod;
use Ennacx\SimpleCurl\Static\Utils;
use InvalidArgumentException;
use RuntimeException;
use Stringable;

/**
 * cURLをシンプルに使用出来るようにラップしたライブラリ
 */
final class SimpleCurlLib {

    /** @var CurlError[] 実行時にエラーとなった場合のリトライ可能とするエラーコード群 */
    private const CURL_EXEC_CONTINUABLE_ERRORS = [
        CurlError::COULDNT_RESOLVE_HOST,
        CurlError::COULDNT_CONNECT,
        CurlError::HTTP_RETURNED_ERROR,
        CurlError::READ_ERROR,
        CurlError::OPERATION_TIMEDOUT,
        CurlError::HTTP_POST_ERROR,
        CurlError::SSL_CONNECT_ERROR,
    ];

    /** @var CurlHandle cURLハンドラー */
    private CurlHandle $ch;

    /** @var string ID */
    private string $id;

    /** @var string|null URL */
    private ?string $url = null;

    /** @var CurlMethod cURLメソッド */
    private CurlMethod $method;

    /** @var array<string, string> HTTPヘッダー */
    private array $_headers = [];

    /** @var boolean 成否問わず、execしたかのフラグ */
    private bool $_executed = false;

    /**
     * cURLをシンプルに使用出来るようにラップしたライブラリ
     *
     * @param  string|null $url            URL
     * @param  CurlMethod  $method         メソッド
     * @param  boolean     $hostVerify     SSL_VERIFYHOST
     * @param  boolean     $certVerify     SSL_VERIFYPEER
     * @param  boolean     $returnTransfer Return transfer
     * @throws RuntimeException
     */
    public function __construct(?string $url = null, CurlMethod $method = CurlMethod::GET, bool $hostVerify = false, bool $certVerify = false, bool $returnTransfer = false){

        if(!extension_loaded('curl')){
            throw new RuntimeException('cURL extension required.');
        }

        $this->url = $url;
        $temp = curl_init($this->url);

        if($temp === false){
            throw new RuntimeException('cURL initialize failed.');
        }

        $this->ch = $temp;

        try{
            $this->id = Utils::generateUUID();
        } catch(RuntimeException){
            throw new RuntimeException('Object-ID generate failed.');
        }

        // メソッド設定
        $this->method = $method;
        switch($this->method){
            case CurlMethod::GET:
                // NOP
                break;
            case CurlMethod::POST:
                curl_setopt($this->ch, CURLOPT_POST, true);
                break;
            case CurlMethod::PUT:
                curl_setopt($this->ch, CURLOPT_PUT, true);
                break;
            case CurlMethod::DELETE:
                curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        // HOSTの検証
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, ($hostVerify) ? 2 : 0);
        // 証明書の検証
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, $certVerify);

        // Return transfer
        $this->setReturnTransfer($returnTransfer);
    }

    /**
     * デストラクタ
     */
    public function __destruct(){

        $this->close();
        unset($this->ch);
    }

    /**
     * cURLセッションを閉じる
     *
     * @return void
     */
    public function close(): void {

        if(isset($this->ch)){
            curl_close($this->ch);
        }
    }

    /**
     * cURLに設定したオプションを全リセットする (URL指定含む)
     *
     * @return void
     */
    public function resetOption(): void {
        curl_reset($this->ch);
    }

    /**
     * ID取得
     *
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * URL取得
     *
     * @return string|null
     */
    public function getUrl(): ?string {
        return $this->url;
    }

    /**
     * cURLメソッド取得
     *
     * @return CurlMethod
     */
    public function getMethod(): CurlMethod {
        return $this->method;
    }

    /**
     * URLを設定
     *
     * @param  string $url
     * @return self
     */
    public function setUrl(string $url): self {

        $this->url = $url;
        curl_setopt($this->ch, CURLOPT_URL, $this->url);

        return $this;
    }

    /**
     * Content-Typeを設定
     *
     * @param  string      $contentType ('application/json', 'text/html' etc...)
     * @param  string|null $charset     文字コード ('UTF-8' etc...)
     * @return $this
     */
    public function setContentType(string $contentType, ?string $charset = null): self {

        $value = $this->_trimLower($contentType, true);

        if($charset !== null){
            $value.= sprintf(';charset=%s', $this->_trimLower($charset, true));
        }

        $this->addHeader(['Content-Type' => $value]);

        return $this;
    }

    /**
     * Acceptを設定
     *
     * @param  string $acceptType ('application/json', 'text/html' etc...)
     * @return $this
     */
    public function setAccept(string $acceptType): self {

        $this->addHeader(['Accept' => $this->_trimLower($acceptType, true)]);

        return $this;
    }

    /**
     * cURL実行結果を文字列で取得するか
     *
     * @param  boolean $flag falseの場合、ボディーは直接出力
     * @return self
     */
    public function setReturnTransfer(bool $flag): self {

        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, $flag);

        if($flag){
            // ヘッダー取得
            curl_setopt($this->ch, CURLOPT_HEADER, true);
        }

        return $this;
    }

    /**
     * Locationヘッダーの内容を辿るか
     *
     * @param  boolean $follow        True: ロケーションを辿る / False: 辿らない
     * @param  int     $redirectCount ```$follow = true の時``` 最大リダイレクト回数
     * @param  boolean $autoReferer   ```$follow = true の時``` True: ヘッダのリファラ情報を自動付与する / False: 付与しない
     * @return self
     */
    public function setFollowLocation(bool $follow, int $redirectCount = 10, bool $autoReferer = true): self {

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $follow);

        if($follow){
            curl_setopt($this->ch, CURLOPT_MAXREDIRS, $redirectCount);
            curl_setopt($this->ch, CURLOPT_AUTOREFERER, $autoReferer);
        }

        return $this;
    }

    /**
     * 認証情報の設定
     *
     * @param  CurlAuth    $method 認証メソッド
     * @param  string|null $user   ユーザーID
     * @param  string|null $pass   パスワード
     * @return self
     */
    public function setAuthentication(CurlAuth $method, ?string $user = null, ?string $pass = null): self {

        curl_setopt($this->ch, CURLOPT_HTTPAUTH, $method->toCurlConst());

        if($method !== CurlAuth::NONE && $user !== null && $pass !== null){
            curl_setopt($this->ch, CURLOPT_USERPWD, "{$user}:{$pass}");
        }

        return $this;
    }

    /**
     * BASIC認証情報の設定
     *
     * @param  string|null $user ユーザーID
     * @param  string|null $pass パスワード
     * @return self
     */
    public function setBasicAuthentication(?string $user = null, ?string $pass = null): self {

        if(!empty($user) && !empty($pass)){
            curl_setopt($this->ch, CURLOPT_HTTPAUTH, CurlAuth::BASIC->toCurlConst());

            $this->addHeader(['Authorization' => sprintf("Basic %s", base64_encode("{$user}:{$pass}"))]);
        }

        return $this;
    }

    /**
     * Bearerトークンの設定
     *
     * @param  string|null $token トークン
     * @return self
     */
    public function setBearerToken(?string $token = null): self {

        if(!empty($token)){
            $this->addHeader(['Authorization' => "Bearer {$token}"]);
        }

        return $this;
    }

    /**
     * リダイレクト回数の上限を設定
     *
     * @param int $count リダイレクト回数
     *              ```
     *              負値でリダイレクトループの無視
     *              0: リダイレクト拒否
     *              ```
     * @return self
     */
    public function setMaxRedirectCount(int $count): self {

        if($count < 0){
            $count = -1;
        }

        $this->setFollowLocation(true);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, $count);

        return $this;
    }

    /**
     * cURLタイムアウト秒数の設定
     *
     * @param  int  $seconds タイムアウト秒数
     * @return self
     */
    public function setTimeoutSeconds(int $seconds): self {

        if($seconds < 0){
            $seconds = 0;
        }

        curl_setopt($this->ch, CURLOPT_TIMEOUT, $seconds);

        return $this;
    }

    /**
     * POSTのボディーを設定
     *
     * @param  mixed   $fields     POST内容
     * @param  boolean $jsonEncode POST内容をJSON化するか
     * @param  int     $jsonFlags  $jsonEncodeがtrueの場合のJSONフラグ値
     * @return self
     * @throws InvalidArgumentException
     */
    public function setPostFields(mixed $fields, bool $jsonEncode = false, int $jsonFlags = 0): self {

        if($jsonEncode){
            $fields = json_encode($fields, $jsonFlags);
            if($fields === false){
                throw new InvalidArgumentException('JSON encode failed.');
            }
        }

        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $fields);

        return $this;
    }

    /**
     * cURLのsetopt()関数ラッパー (特殊な設定時に使用)
     *
     * @link https://www.php.net/manual/ja/function.curl-setopt.php
     *
     * @param  int|array $option
     * @param  mixed     $value
     * @return self
     * @throws InvalidArgumentException
     */
    public function setOption(int|array $option, mixed $value = null): self {

        if(is_array($option)){
            $result = curl_setopt_array($this->ch, $option);
            if(!$result){
                throw new InvalidArgumentException('Invalid cURL option or value included.');
            }
        } else if($value !== null){
            $result = curl_setopt($this->ch, $option, $value);
            if(!$result){
                throw new InvalidArgumentException('cURL option contains an invalid value or itself is illegal.');
            }
        }

        return $this;
    }

    /**
     * cURLハンドラー取得
     *
     * @return CurlHandle
     */
    public function getHandler(): CurlHandle {
        return $this->ch;
    }

    /**
     * ヘッダー情報の取得
     *
     * @param  string|null     $separate
     * @return string[]|string
     *                 ```
     *                 ```$separate = null``` 各ヘッダー情報の配列
     *                 ```$separate = string``` 指定文字列で結合した文字列
     *                 ```
     */
    public function getHeader(?string $separate = null): array|string {
        return $this->_headerReformation($separate);
    }

    /**
     * cURLで送信するヘッダーの設定
     *
     * @param  array<string, string>|string[]|string $argHeader
     *             ```
     *             Allow examples
     *                 ['Content-Type' => 'Application/json', ...]
     *                 ['Content-Type: Application/json', ...]
     *                 'Content-Type: Application/json'
     *             ```
     * @return void
     * @throws InvalidArgumentException
     */
    public function addHeader(array|string $argHeader): void {

        /**
         * 最初のコロンでkeyとvalueに分割するサブファンクション
         *
         * @param  string $headerStr (ex. ``` 'Content-Type: application/json' ```)
         * @return array<string, string> (ex. ```['Content-Type' => 'application/json']```)
         * @throws InvalidArgumentException
         */
        $separateFunc = function(string $headerStr): array {

            $temp = array_map(fn(string $v): string => trim($v), explode(':', $headerStr, 2));
            if(count($temp) < 2 || empty($temp[0]) || empty($temp[1])){
                throw new InvalidArgumentException(sprintf('\'%s\' is invalid header format.', $headerStr));
            }

            $key = $this->_headerKeyReformer($temp[0]);

            return [$key => trim($temp[1])];
        };

        if(is_string($argHeader)){
            if(str_contains($argHeader, ':')){
                $this->_headers = array_merge($this->_headers, $separateFunc($argHeader));
            } else{
                throw new InvalidArgumentException(sprintf('\'%s\' is invalid header format.', $argHeader));
            }
        } else{
            $headers = [];
            foreach($argHeader as $k => $v){
                if(is_string($k)){
                    $result = preg_match('/^\s*(?<header_key>(\w+(-\w+)*)+)\s*:?\s*$/', $k, $matches);
                    if($result === 1){
                        // キー取得
                        $k = $matches['header_key'] ?? '';
                        // 値取得
                        if(is_string($v)){
                            $temp = $v;
                        } else if(is_numeric($v)){
                            $temp = strval($v);
                        } else if($v instanceof Stringable){
                            $temp = $v->__toString();
                        } else{
                            throw new InvalidArgumentException(sprintf('Key: \'%s\' is invalid header format value.', $k));
                        }
                        $v = trim($temp);
                        unset($temp);

                        if(!empty($k) && !empty($v)){
                            $headers[$this->_headerKeyReformer($k)] = $v;
                        }
                    }
                } else if(str_contains($v, ':')){
                    $temp = $separateFunc($v);
                    $headers[key($temp)] = current($temp);
                    unset($temp);
                } else{
                    throw new InvalidArgumentException(sprintf('\'%s\': \'%s\' is invalid header format.', $k, $v));
                }
            }

            $this->_headers = array_merge($this->_headers, $headers);
        }
    }

    /**
     * ヘッダーの削除
     *
     * @param  string  $key 削除するヘッダー
     * @return boolean 存在しなければfalse
     */
    public function removeHeader(string $key): bool {

        $key = $this->_headerKeyReformer($key);

        if(!array_key_exists($key, $this->_headers)){
            return false;
        }

        unset($this->_headers[$key]);

        return true;
    }

    /**
     * cURLで送信するヘッダー情報の全削除
     *
     * @return void
     */
    public function resetHeader(): void {
        $this->_headers = [];
    }

    /**
     * cURL実行
     *
     * @param  int     $retries リトライ回数
     * @param  boolean $throws  True: throw exception / False: return void
     * @return ResponseEntity
     * @throws RuntimeException
     */
    public function exec(int $retries = 5, bool $throws = false): ResponseEntity {

        // コード番号変換
        $continuableErrorCodes = array_map(fn(CurlError $v): int => $v->value, self::CURL_EXEC_CONTINUABLE_ERRORS);

        /**
         * cURLのレスポンスメタ情報をセットするサブファンクション
         *
         * @param  ResponseEntity $entity
         * @return void
         */
        $getInfoFunc = function(ResponseEntity $entity): void {
            $temp = curl_getinfo($this->ch);
            if(is_array($temp)){
                $entity->setInfo($temp);
            }
        };

        // ヘッダー情報の付与
        $strHeaders = $this->_headerReformation();
        if(!empty($strHeaders)){
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $strHeaders);
        }

        // 返却用エンティティー作成
        $responseEntity = new ResponseEntity();

        while($retries--){
            // cURLリクエスト実行
            $curlResult = curl_exec($this->ch);

            // cURL実行フラグ
            $this->_executed = true;

            $responseEntity->id  = $this->id;
            $responseEntity->url = $this->url;

            // ReturnTransfer無効時、またはcURL失敗時
            if(is_bool($curlResult)){
                $responseEntity->result = $curlResult;

                $responseEntity->responseHeader = null;
                $responseEntity->responseBody   = null;

                // cURL失敗時はエラー情報を格納
                if($curlResult === false){
                    if(!in_array(curl_errno($this->ch), $continuableErrorCodes, true) || $retries === 0){
                        $responseEntity->errorEnum    = CurlError::fromValue(curl_errno($this->ch));
                        $responseEntity->errorMessage = curl_error($this->ch);

                        if($throws)
                            throw new RuntimeException(sprintf('cURL error (Code: %d): %s', $responseEntity->errorEnum->value, $responseEntity->errorMessage));
                        else
                            return $responseEntity;
                    }
                } else{
                    $responseEntity->errorEnum    = CurlError::OK;
                    $responseEntity->errorMessage = '';

                    // レスポンスメタ情報のセット
                    $getInfoFunc($responseEntity);

                    break;
                }
            }
            // ReturnTransfer有効、且つcURL成功時
            else if(!empty($curlResult)){
                $responseEntity->result = true;

                $responseEntity->errorEnum    = CurlError::OK;
                $responseEntity->errorMessage = '';

                // レスポンスメタ情報のセット
                $getInfoFunc($responseEntity);

                // ヘッダー情報とボディー情報を分割
                $headerSize = $responseEntity->getInfo()['header_size'] ?? null;
                if($headerSize !== null){
                    $responseEntity->responseHeader = trim(substr($curlResult, 0, $headerSize));
                    $responseEntity->responseBody   = substr($curlResult, $headerSize);
                } else{
                    $responseEntity->responseHeader = null;
                    $responseEntity->responseBody   = $curlResult;
                }

                break;
            }
            // 理論上ここには入らない
            else{
                throw new RuntimeException('Unknown error.');
            }
        }

        // 最終確認
        if(!$this->_executed){
            throw new InvalidArgumentException('cURL finished without being executed.');
        }

        return $responseEntity;
    }

    /**
     * keyからスペースをなくし整形 (ex. ``` 'c ONtenT - tY  PE' ```を``` 'Content-Type' ``` に変換)
     *
     * @param  string $key
     * @return string
     */
    private function _headerKeyReformer(string $key): string {
        return ucwords(str_replace(' ', '', $key), '-');
    }

    /**
     * ヘッダー整形
     *
     * @param  string|null     $separate
     * @return string[]|string
     */
    private function _headerReformation(?string $separate = null): array|string {

        $ret = [];

        // ヘッダー情報の付与
        if(!empty($this->_headers)){
            foreach($this->_headers as $k => $v){
                $ret[] = "{$k}: {$v}";
            }
        }

        return ($separate !== null) ? implode($separate, $ret) : $ret;
    }

    /**
     * 小文字にして両端の空白を除去
     *
     * @param  string  $v
     * @param  boolean $spaceAllRemove
     * @return string
     */
    private function _trimLower(string $v, bool $spaceAllRemove = false): string {

        $temp = strtolower($v);

        return ($spaceAllRemove) ? str_replace(' ', '', $temp) : trim($temp);
    }


    public function test(?string $a){
        $this->id = $a;
    }
}
