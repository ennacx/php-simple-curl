<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

/**
 * RequestとCurlOptionsを組み合わせた送信準備済みリクエスト。
 */
final readonly class PreparedRequest {

    /**
     * コンストラクタ
     *
     * @param Request          $request 送信するHTTPリクエスト
     * @param CurlOptions|null $options cURL実行オプション。nullの場合はデフォルト設定で実行する
     */
    private function __construct(public Request $request, public ?CurlOptions $options = null){
    }

    /**
     * 送信準備済みリクエストを生成する。
     *
     * @param  Request          $request
     * @param  CurlOptions|null $options
     * @return self
     */
    public static function create(Request $request, ?CurlOptions $options = null): self {
        return new self($request, $options);
    }
}
