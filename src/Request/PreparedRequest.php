<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Request;

use Ennacx\SimpleCurl\Option\CurlOptions;

/**
 * A request prepared with cURL execution options.
 */
final readonly class PreparedRequest {

    /**
     * コンストラクタ。
     *
     * @param Request     $request 送信するHTTPリクエスト
     * @param CurlOptions $options 実行オプション
     */
    private function __construct(private Request $request, private CurlOptions $options){
    }

    /**
     * Creates a prepared request instance.
     *
     * @param Request          $request Request to send.
     * @param CurlOptions|null $options Execution options.
     */
    public static function create(Request $request, ?CurlOptions $options = null): self {
        return new self($request, $options ?? CurlOptions::create());
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
    public function getOptions(): CurlOptions {
        return $this->options;
    }
}
