<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * HTTPで扱う代表的なメディアタイプを表す列挙型。
 *
 * 現時点では主にリクエストボディーのContent-Type指定に使用する。
 * AcceptヘッダーやレスポンスContent-Type判定など、メディアタイプを扱う用途にも再利用できる。
 */
enum ContentType : string {

    /** プレーンテキスト (`text/plain`) */
    case PlainText = 'text/plain';

    /** HTML (`text/html`) */
    case Html = 'text/html';

    /** JSON (`application/json`) */
    case Json = 'application/json';

    /** XML (`application/xml`) */
    case Xml = 'application/xml';

    /** URLエンコード済みフォーム (`application/x-www-form-urlencoded`) */
    case FormUrlEncoded = 'application/x-www-form-urlencoded';

    /** PDF (`application/pdf`) */
    case Pdf = 'application/pdf';

    /** バイナリデータ (`application/octet-stream`) */
    case OctetStream = 'application/octet-stream';

    /**
     * Content-Typeヘッダーを配列またはcURL用のヘッダー文字列として取得する。
     *
     * @param  boolean $returnArray trueの場合は連想配列、falseの場合は "Content-Type: ..." 形式で返す
     * @return array{'Content-Type': string}|string
     */
    public function getContentTypeHeader(bool $returnArray = true): array|string {
        return ($returnArray) ?
            ['Content-Type' => $this->value] :
            sprintf('Content-Type: %s', $this->value);
    }
}
