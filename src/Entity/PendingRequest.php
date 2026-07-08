<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

/**
 * RequestとCurlOptionsを組み合わせた送信待ちリクエスト。
 */
final class PendingRequest {

    /**
     * コンストラクタ
     *
     * @param Request          $request 送信するHTTPリクエスト
     * @param CurlOptions|null $options cURL実行オプション。nullの場合はデフォルト設定で実行する
     */
    private function __construct(public Request $request, public ?CurlOptions $options = null){
    }

    /**
     * 送信待ちリクエストを生成する。
     *
     * @param  Request          $request
     * @param  CurlOptions|null $options
     * @return self
     */
    public static function create(Request $request, ?CurlOptions $options = null): self {
        return new self($request, $options);
    }
}
