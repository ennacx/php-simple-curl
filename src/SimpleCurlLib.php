<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

use CurlHandle;
use Ennacx\SimpleCurl\Entity\ResponseEntity;
use Ennacx\SimpleCurl\Enum\CurlAuth;
use Ennacx\SimpleCurl\Enum\CurlError;
use Ennacx\SimpleCurl\Enum\CurlMethod;
use Ennacx\SimpleCurl\Enum\ProxyAuth;
use Ennacx\SimpleCurl\Enum\ProxyProtocol;
use Ennacx\SimpleCurl\Enum\SSLVersion;
use Ennacx\SimpleCurl\Static\Utils;
use Ennacx\SimpleCurl\Trait\CurlLibTrait;
use InvalidArgumentException;
use RuntimeException;
use Stringable;

/**
 * cURLをシンプルに使用出来るようにラップしたライブラリ
 */
final class SimpleCurlLib {

    /* Lib共通使用トレイト */
    use CurlLibTrait;

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

    /** @var array<int, mixed> cURLオプション */
    private array $_options = [];

    /** @var string|null Cookieデータ保存パス */
    private ?string $_cookieFilePath = null;

    /** @var boolean 成否問わず、execしたかのフラグ */
    private bool $_executed = false;

    /**
     * cURLをシンプルに使用出来るようにラップしたライブラリ
     *
     * @param  string|null $url            URL
     * @param  CurlMethod  $method         メソッド
     * @param  string|null $cookiePath     Cookie使用時のファイルパス
     * @param  boolean     $hostVerify     SSL_VERIFYHOST
     * @param  boolean     $returnTransfer Return transfer
     * @throws RuntimeException
     */
    public function __construct(?string $url = null, CurlMethod $method = CurlMethod::GET, ?string $cookiePath = null, bool $hostVerify = false, bool $returnTransfer = false){

        if(!extension_loaded('curl'))
            throw new RuntimeException('cURL extension required.');

        if(!extension_loaded('openssl'))
            throw new RuntimeException('OpenSSL extension required.');

        $this->url = $url;
        $temp = curl_init($this->url);

        if($temp === false)
            throw new RuntimeException('cURL initialize failed.');

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
                $this->_setOption(CURLOPT_POST, true);
                break;
            case CurlMethod::PUT:
                $this->_setOption(CURLOPT_PUT, true);
                break;
            case CurlMethod::DELETE:
                $this->_setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        // Cookie
        if($cookiePath !== null)
            $this->setCookieFile($cookiePath);

        // HOSTの検証
        $this->_setOption(CURLOPT_SSL_VERIFYHOST, ($hostVerify) ? 2 : 0);
        // 証明書の検証はデフォルトではfalse
        $this->_setOption(CURLOPT_SSL_VERIFYPEER, false);

        // Return transfer
        $this->setReturnTransfer(returnTransfer: $returnTransfer, returnHeader: true);
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

        if(isset($this->ch))
            curl_close($this->ch);
    }

    /**
     * cURL実行確認
     *
     * @return boolean True: 実行済 / False: 未実行
     */
    public function isExecuted(): bool {
        return $this->_executed;
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
        $this->_setOption(CURLOPT_URL, $this->url);

        return $this;
    }

    /**
     * Content-Typeを設定
     *
     * @param  string      $contentType ('application/json', 'text/html' etc...)
     * @param  string|null $charset     文字コード ('UTF-8' etc...)
     * @return self
     */
    public function setContentType(string $contentType, ?string $charset = null): self {

        $value = Utils::trimLower($contentType, true);

        if($charset !== null)
            $value.= sprintf(';charset=%s', Utils::trimLower($charset, true));

        $this->addHeader(['Content-Type' => $value]);

        return $this;
    }

    /**
     * SSL証明書の設定
     *
     * @param  string  $pemPath    証明書までのパス
     * @param  boolean $certVerify 証明書検証 (Default: true)
     * @return self
     */
    public function setCert(string $pemPath, bool $certVerify = true): self {

        if(!file_exists($pemPath))
            throw new InvalidArgumentException('Certificate file not found.');

        // PEM
        $this->_setOption(CURLOPT_CAINFO, $pemPath);

        // 証明書の検証
        $this->_setOption(CURLOPT_SSL_VERIFYPEER, $certVerify);

        return $this;
    }

    /**
     * プロキシ接続設定
     *
     * @param  string        $proxyAddr  IP-Address or URL
     * @param  int           $port       Proxy port number
     * @param  ProxyProtocol $protocol   Proxy protocol
     * @param  bool|null     $httpTunnel Tunnel through the specified HTTP proxy.
     * @return self
     */
    public function setProxy(string $proxyAddr, int $port = 3128, ProxyProtocol $protocol = ProxyProtocol::HTTP, ?bool $httpTunnel = null): self {

        $this->_setOption(CURLOPT_PROXY,     $proxyAddr);
        $this->_setOption(CURLOPT_PROXYPORT, $port);

        $this->_setOption($protocol->toCurlConst(), $httpTunnel);

        if($httpTunnel !== null)
            $this->_setOption(CURLOPT_HTTPPROXYTUNNEL, $httpTunnel);

        return $this;
    }

    /**
     * プロキシーへの認証情報の設定
     *
     * @param ProxyAuth   $method 認証メソッド
     * @param string|null $user   ユーザーID
     * @param string|null $pass   パスワード
     * @return $this
     */
    public function setProxyAuthentication(ProxyAuth $method, ?string $user = null, ?string $pass = null): self {

        if($this->_hasOption(CURLOPT_PROXY) && $this->_hasOption(CURLOPT_PROXYPORT)){
            if($method !== ProxyAuth::NONE){
                $this->_setOption(CURLOPT_PROXYAUTH, $method->toCurlConst());

                if($user !== null && $pass !== null)
                    $this->_setOption(CURLOPT_PROXYUSERPWD, "{$user}:{$pass}");
            }
        }

        return $this;
    }

    /**
     * cURL実行結果を文字列で取得するか
     *
     * @param  boolean $returnTransfer falseの場合、ボディーは直接出力
     * @param  boolean $returnHeader   ```$returnTransfer = true の時```ヘッダーも返却するか
     * @return self
     */
    public function setReturnTransfer(bool $returnTransfer, bool $returnHeader = true): self {

        $this->_setOption(CURLOPT_RETURNTRANSFER, $returnTransfer);

        // ReturnTransfer有効時はヘッダー情報も合わせて取得するか設定
        if($returnTransfer)
            $this->_setOption(CURLOPT_HEADER, $returnHeader);

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

        $this->_setOption(CURLOPT_FOLLOWLOCATION, $follow);

        if($follow){
            $this->_setOption(CURLOPT_MAXREDIRS, $redirectCount);
            $this->_setOption(CURLOPT_AUTOREFERER, $autoReferer);
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

        $this->_setOption(CURLOPT_HTTPAUTH, $method->toCurlConst());

        if($method !== CurlAuth::NONE && $user !== null && $pass !== null)
            $this->_setOption(CURLOPT_USERPWD, "{$user}:{$pass}");

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
            $this->_setOption(CURLOPT_HTTPAUTH, CurlAuth::BASIC->toCurlConst());

            $this->addHeader([
                'Authorization' => sprintf("Basic %s", base64_encode("{$user}:{$pass}"))
            ]);
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
            $this->addHeader([
                'Authorization' => sprintf('Bearer %s', $token)
            ]);
        }

        return $this;
    }

    /**
     * Cookie保存ファイルパスの設定
     *
     * @param  string $cookieFilePath
     * @return self
     */
    public function setCookieFile(string $cookieFilePath): self {

        $this->_cookieFilePath = $cookieFilePath;

        return $this;
    }

    /**
     * UserAgentの設定
     *
     * @param  string $userAgent
     * @return self
     */
    public function setUA(string $userAgent): self {

        $this->_setOption(CURLOPT_USERAGENT, $userAgent);

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

        if($count < 0)
            $count = -1;

        $this->setFollowLocation(true);
        $this->_setOption(CURLOPT_MAXREDIRS, $count);

        return $this;
    }

    /**
     * cURLタイムアウト秒数の設定
     *
     * @param  int  $seconds タイムアウト秒数
     * @return self
     */
    public function setTimeoutSeconds(int $seconds): self {

        if($seconds < 0)
            $seconds = 0;

        $this->_setOption(CURLOPT_TIMEOUT, $seconds);

        return $this;
    }

    /**
     * GETクエリーを設定
     *
     * @param  array|string $query          リクエストパラメーターの配列またはURLエンコード化されたクエリ文字列
     * @param  string       $numeric_prefix ```$query```がインデックスを含む配列だった場合、インデックス前に付与する文字列
     * @return self
     */
    public function setGetQuery(array|string $query, string $numeric_prefix = ''): self {

        if(!empty($this->url) && !empty($query)){
            $strQuery = (is_array($query)) ? http_build_query($query, $numeric_prefix) : $query;

            $parse = parse_url($this->url);

            $this->url = (!empty($parse['query'])) ?
                // GETクエリの箇所のみ置換
                str_replace($parse['query'], $strQuery, $this->url) :
                // 組み立て直し
                sprintf(
                    '%s://%s%s%s?%s%s',
                    $parse['scheme'],
                    (!empty($parse['user'])) ? sprintf('%s:%s@', $parse['user'], $parse['pass'] ?? '') : '',
                    $parse['host'],
                    $parse['path'],
                    $strQuery,
                    (!empty($parse['fragment']) ? "#{$parse['fragment']}" : '')
                );

            unset($strQuery, $parse);
        }

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
            if($fields === false)
                throw new InvalidArgumentException('JSON encode failed.');
        }

        $this->_setOption(CURLOPT_POSTFIELDS, $fields);

        return $this;
    }

    /**
     * Acceptを設定
     *
     * @param  string $acceptType ('application/json', 'text/html' etc...)
     * @return self
     */
    public function setAccept(string $acceptType): self {

        $this->addHeader([
            'Accept' => Utils::trimLower($acceptType, true)
        ]);

        return $this;
    }

    /**
     * エンコード済レスポンスデータの展開有効化
     *
     * @param  string $encode ```identity, gzip, deflate``` separate them with comma
     * @return self
     */
    public function setEncoding(string $encode = 'gzip'): self {

        $this->_setOption(CURLOPT_ENCODING, $encode);

        return $this;
    }

    /**
     * SSLバージョンを指定
     *
     * @param  SSLVersion $sslVersion
     * @return self
     */
    public function setSSLVersion(SSLVersion $sslVersion): self {

        $this->_setOption(CURLOPT_SSLVERSION, $sslVersion->toCurlConst());

        return $this;
    }

    /**
     * cURLにオプションを設定
     *
     * @link https://www.php.net/manual/ja/function.curl-setopt.php
     *
     * @param  int|array $option    PHPの```CURLOPT_XXX```定数値または```[CURLOPT_XXX => Value]```の配列
     * @param  mixed     $value     ```$option```がint時の、そのオプションに対する設定値
     * @param  boolean   $overwrite True: 既に設定されている場合は上書き / False: 既存を優先
     * @return self
     * @throws InvalidArgumentException
     */
    public function setOption(int|array $option, mixed $value = null, bool $overwrite = true): self {

        if(is_array($option)){
            if($overwrite){
                $this->_options = array_replace($this->_options, $option);
            } else{
                // 既存に存在しないキーを取得
                // FIXME: $this->_options += $option でも前変数のキーを優先するので大丈夫そうなのだが念のため
                $diff = array_diff(array_keys($option), array_keys($this->_options));
                if(!empty($diff)){
                    // 存在しないキーのみ追加
                    $this->_options = array_replace(
                        $this->_options,
                        array_filter($option, fn($k): bool => (is_int($k) && in_array($k, $diff, true)), ARRAY_FILTER_USE_KEY)
                    );
                }
                unset($diff);
            }
        } else if($value !== null){
            if(!array_key_exists($option, $this->_options) || $overwrite)
                $this->_options[$option] = $value;
        }

        return $this;
    }

    /**
     * cURLに設定したオプションを全リセットする (URL指定含む)
     *
     * @return self
     */
    public function resetOption(): self {

        $this->_options = [];
        curl_reset($this->ch);

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
     *                 ```$separate = null``` 各ヘッダー情報の配列
     *                 ```$separate = string``` 指定文字列で結合した文字列
     */
    public function getHeader(?string $separate = null): array|string {
        return $this->_headerReformation(separate: $separate);
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
     * @return self
     * @throws InvalidArgumentException
     */
    public function addHeader(array|string $argHeader): self {

        /**
         * 最初のコロンでkeyとvalueに分割するサブファンクション
         *
         * @param  string $headerStr (ex. ``` 'Content-Type: application/json' ```)
         * @return array<string, string> (ex. ```['Content-Type' => 'application/json']```)
         * @throws InvalidArgumentException
         */
        $separateFunc = function(string $headerStr): array {

            $temp = array_map('trim', explode(':', $headerStr, 2));
            if(count($temp) < 2 || empty($temp[0]) || empty($temp[1]))
                throw new InvalidArgumentException(sprintf('\'%s\' is invalid header format.', $headerStr));

            $key = $this->_headerKeyReformer($temp[0]);

            return [$key => trim($temp[1])];
        };

        if(is_string($argHeader)){
            if(str_contains($argHeader, ':'))
                $this->_headers = array_merge($this->_headers, $separateFunc($argHeader));
            else
                throw new InvalidArgumentException(sprintf('\'%s\' is invalid header format.', $argHeader));
        } else{
            $headers = [];
            foreach($argHeader as $k => $v){
                if(is_string($k)){
                    $result = preg_match('/^\s*(?<header_key>(\w+(-\w+)*)+)\s*:?\s*$/', $k, $matches);
                    if($result === 1){
                        // キー取得
                        $k = $matches['header_key'] ?? '';

                        // 値取得
                        if(is_string($v))
                            $temp = $v;
                        else if(is_numeric($v))
                            $temp = strval($v);
                        else if($v instanceof Stringable)
                            $temp = $v->__toString();
                        else
                            throw new InvalidArgumentException(sprintf('Key: \'%s\' is invalid header format value.', $k));
                        $v = trim($temp);
                        unset($temp);

                        // キーと値が正常な場合ヘッダーに追加
                        if(!empty($k) && !empty($v))
                            $headers[$this->_headerKeyReformer($k)] = $v;
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

        return $this;
    }

    /**
     * ヘッダーの削除
     *
     * @param  string  $key 削除するヘッダー
     * @return boolean 存在しなければfalse
     */
    public function removeHeader(string $key): bool {

        $k = $this->_headerKeyReformer($key);

        if(!array_key_exists($k, $this->_headers))
            return false;

        unset($this->_headers[$k]);

        return true;
    }

    /**
     * cURLで送信するヘッダー情報の全削除
     *
     * @return self
     */
    public function resetHeader(): self {

        $this->_headers = [];

        return $this;
    }

    /**
     * CurlHandlerにオプションをアタッチ
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function attachOptionToHandler(): void {

        // ヘッダー情報の付与
        $strHeaders = $this->_headerReformation(separate: null);
        if(!empty($strHeaders))
            $this->_setOption(CURLOPT_HTTPHEADER, $strHeaders);

        // Cookie使用時の設定
        if($this->_cookieFilePath !== null){
            // 保存用
            $this->_setOption(CURLOPT_COOKIEJAR, $this->_cookieFilePath);
            // 取得用
            $this->_setOption(CURLOPT_COOKIEFILE, $this->_cookieFilePath);
        }

        // POSTメソッドでなければPOSTフィールド削除
        if($this->method !== CurlMethod::POST){
            if(array_key_exists(CURLOPT_CUSTOMREQUEST, $this->_options) && strtoupper(trim($this->_options[CURLOPT_CUSTOMREQUEST])) === 'POST')
                unset($this->_options[CURLOPT_CUSTOMREQUEST]);

            if(array_key_exists(CURLOPT_POSTFIELDS, $this->_options))
                unset($this->_options[CURLOPT_POSTFIELDS]);
        }

        // CurlHandlerにオプションを設定
        if(count($this->_options) > 0){
            if(!curl_setopt_array($this->ch, $this->_options))
                throw new InvalidArgumentException('Invalid cURL option or value included.');
        }
    }

    /**
     * cURL実行
     *
     * @param  int     $retries 実行失敗時のリトライ回数
     * @param  boolean $throw   True: throw exception / False: return void
     * @return ResponseEntity
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function exec(int $retries = 5, bool $throw = false): ResponseEntity {

        // リトライ可能エラーコードの番号変換
        $continuableErrorCodes = array_map(fn(CurlError $v): int => $v->value, self::CURL_EXEC_CONTINUABLE_ERRORS);

        // CurlHandlerにオプションをアタッチ
        $this->attachOptionToHandler();

        // 返却用エンティティー作成
        $responseEntity = $this->newResponseEntity();

        while($retries--){
            // cURLリクエスト実行
            $curlResult = curl_exec($this->ch);

            // cURL実行フラグ
            $this->_executed = true;

            $responseEntity->id  = $this->id;
            $responseEntity->url = $this->url;

            // レスポンスメタ情報のセット
            $this->setCurlInfoMeta($this->ch, $responseEntity);

            // cURLの実行結果、レスポンス、エラーをエンティティーにセット
            $breakable = $this->setResponseToEntity(
                curlResult: $curlResult,
                entity: $responseEntity,
                divideHeader: $this->_getOption(CURLOPT_HEADER),
                option: compact('continuableErrorCodes', 'retries', 'throw')
            );

            if($breakable)
                break;
        }

        // 最終確認
        if(!$this->_executed)
            throw new InvalidArgumentException('cURL finished without being executed.');

        return $responseEntity;
    }

    /**
     * cURLオプションが設定されているか
     *
     * @param  int     $curlOpt
     * @return boolean
     */
    private function _hasOption(int $curlOpt): bool {
        return (array_key_exists($curlOpt, $this->_options));
    }

    /**
     * 適用するcURLオプション取得
     *
     * @param  int|null                $curlOpt PHPの```CURLOPT_XXX```定数値指定で対象値、未指定で全取得
     * @return array<int, mixed>|mixed
     */
    private function _getOption(?int $curlOpt = null): mixed {

        if($curlOpt === null)
            return $this->_options;

        return (array_key_exists($curlOpt, $this->_options)) ? $this->_options[$curlOpt] : null;
    }

    /**
     * cURLオプションを設定
     *
     * @param  int   $key   PHPの```CURLOPT_XXXX```定数
     * @param  mixed $value cURLオプション設定値
     * @return void
     */
    private function _setOption(int $key, mixed $value): void {
        $this->_options[$key] = $value;
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
     * @param  string|null     $separate null時は配列
     * @return string[]|string
     */
    private function _headerReformation(?string $separate = null): array|string {

        $ret = [];

        // ヘッダー情報の付与
        if(!empty($this->_headers)){
            foreach($this->_headers as $k => $v)
                $ret[] = "{$k}: {$v}";
        }

        return ($separate !== null) ? implode($separate, $ret) : $ret;
    }
}
