<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

/**
 * A request prepared with optional cURL execution options.
 */
final readonly class PreparedRequest {

    /**
     * コンストラクタ。
     *
     * @param Request          $request 送信するHTTPリクエスト
     * @param CurlOptions|null $options 実行オプション。nullの場合はデフォルト設定で実行する
     */
    private function __construct(private Request $request, private ?CurlOptions $options = null){
    }

    /**
     * Creates a prepared request instance.
     *
     * @param Request          $request Request to send.
     * @param CurlOptions|null $options Execution options.
     */
    public static function create(Request $request, ?CurlOptions $options = null): self {
        return new self($request, $options);
    }

    /**
     * Returns the request.
     */
    public function getRequest(): Request {
        return $this->request;
    }

    /**
     * Returns execution options.
     */
    public function getOptions(): ?CurlOptions {
        return $this->options;
    }
}
