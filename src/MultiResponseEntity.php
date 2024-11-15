<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

/**
 * MultiCurlLib::exec()メソッドのcURL各結果
 */
class MultiResponseEntity {

    /** @var string SimpleCurlLibのID */
    public string $id;

    /** @var string SimpleCurlLibで設定したURL */
    public string $url;

    /** @var boolean cURL成功可否 */
    public bool $result;

    /** @var float|null 合計時間 (秒) */
    public ?float $totalTime = null;

    /** @var string|null cURLレスポンスヘッダー */
    public ?string $responseHeader = null;

    /** @var string|null cURLレスポンスボディー */
    public ?string $responseBody = null;

    /** @var CurlError|null cURLエラーEnum */
    public ?CurlError $errorEnum = null;

    /** @var string|null cURLエラーメッセージ */
    public ?string $errorMessage = null;
}