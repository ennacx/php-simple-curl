<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * リクエストボディーへ付与するContent-Typeを表す列挙型。
 *
 * MultipartやStreamなどのアップロード系Content-Typeは、
 * ファイル送信APIの実装時に追加する。
 */
enum RequestContentType {

    /** プレーンテキスト本文 (`text/plain`) */
    case PlainText;

    /** JSON本文 (`application/json`) */
    case Json;

    /** URLエンコード済みフォーム本文 (`application/x-www-form-urlencoded`) */
    case FormUrlEncoded;

    /**
     * Content-Typeヘッダーの値を取得する。
     *
     * @return string
     */
    public function getContentType(): string {
        return match($this) {
            self::PlainText      => 'text/plain',
            self::Json           => 'application/json',
            self::FormUrlEncoded => 'application/x-www-form-urlencoded',
        };
    }

    /**
     * Content-Typeヘッダーを配列またはcURL用のヘッダー文字列として取得する。
     *
     * @param  boolean $returnArray trueの場合は連想配列、falseの場合は "Content-Type: ..." 形式で返す
     * @return array{'Content-Type': string}|string
     */
    public function getContentTypeHeader(bool $returnArray = true): array|string {
        return ($returnArray) ?
            ['Content-Type' => $this->getContentType()] :
            sprintf('Content-Type: %s', $this->getContentType());
    }
}
