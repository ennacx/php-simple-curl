<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ローカルHTTP fixture serverを使うインテグレーションテストの基底クラス。
 *
 * PHPUnit実行時にPHP built-in serverを空きポートで起動し、
 * 外部ネットワークに依存せずSingleClient/MultiClientの実通信を検証する。
 */
abstract class LocalHttpServerTestCase extends TestCase {

    private const HOST = '127.0.0.1';

    /** @var resource|null PHP built-in serverのプロセスリソース */
    private static $process = null;

    /** @var array<int, resource> `proc_open()` で接続した標準入出力パイプ */
    private static array $pipes = [];

    /** @var int テストごとに割り当てるローカルHTTP serverのポート */
    private static int $port;

    /**
     * テストクラス実行前にローカルHTTP serverを起動する。
     */
    public static function setUpBeforeClass(): void {

        self::$port = self::findFreePort();
        $router = dirname(__DIR__) . '/Fixtures/http_server.php';

        // `php -S`を用いたビルトインサーバーを立ち上げる
        $process = proc_open(
            // コマンド
            [
                PHP_BINARY,
                '-S',
                sprintf('%s:%d', self::HOST, self::$port),
                $router,
            ],
            // ディスクリプタ指定
            [
                0 => ['pipe', 'r'], // stdin
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w'], // stderr
            ],
            // PHP側で生成されたパイプの終端にあたるファイルポインタ配列
            $pipes,
            // コマンドの初期作業ディレクトリ
            dirname(__DIR__, 2),
        );

        if(!is_resource($process)){
            self::fail('Failed to start local fixture server.');
        }

        self::$process = $process;
        self::$pipes   = $pipes;

        // stdinは使用しないのでクローズ
        fclose(self::$pipes[0]);

        // 起動完了まで待機
        self::waitUntilServerIsReady();
    }

    /**
     * テストクラス実行後にローカルHTTP serverを停止する。
     */
    public static function tearDownAfterClass(): void {

        foreach(self::$pipes as $pipe){
            if(is_resource($pipe)){
                fclose($pipe);
            }
        }

        if(is_resource(self::$process)){
            proc_terminate(self::$process);
            proc_close(self::$process);
        }

        self::$process = null;
        self::$pipes = [];
    }

    /**
     * ローカルHTTP server上のURLを生成する。
     *
     * @param string $path `/json` のようなfixture path
     */
    protected static function url(string $path): string {

        if($path === '' || $path[0] !== '/'){
            $path = '/' . $path;
        }

        return sprintf('http://%s:%d%s', self::HOST, self::$port, $path);
    }

    /**
     * ローカルHTTP server用の空きポートを取得する。
     */
    private static function findFreePort(): int {

        $server = stream_socket_server(sprintf('tcp://%s:0', self::HOST), $errno, $errstr);
        if($server === false){
            self::fail(sprintf('Failed to find free port: %s', $errstr));
        }

        $name = stream_socket_get_name($server, false);
        fclose($server);

        if($name === false){
            self::fail('Failed to resolve free port.');
        }

        $port = parse_url(sprintf('tcp://%s', $name), PHP_URL_PORT);
        if(!is_int($port)){
            self::fail('Failed to parse free port.');
        }

        return $port;
    }

    /**
     * `/health` が応答するまで待機する。
     * ※サーバー起動直後はリッスンが完了していないことがあるため、短時間だけポーリングする。
     */
    private static function waitUntilServerIsReady(): void {

        $deadline = microtime(true) + 5.0;
        $url = self::url('/health');

        do{
            $body = @file_get_contents($url);
            if($body === 'ok'){
                return;
            }

            usleep(50000);
        } while(microtime(true) < $deadline);

        // stderrをストリームから取得
        $stderr = '';
        if(isset(self::$pipes[2]) && is_resource(self::$pipes[2])){
            stream_set_blocking(self::$pipes[2], false);
            $stderr = stream_get_contents(self::$pipes[2]) ?: '';
        }

        self::fail(sprintf('Local fixture server did not become ready. %s', trim($stderr)));
    }
}
