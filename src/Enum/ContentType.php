<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

use Ennacx\SimpleCurl\Entity\AcceptValue;
use Ennacx\SimpleCurl\Entity\QualifiedAcceptValue;

/**
 * HTTPで扱う代表的なメディアタイプを表す列挙型。
 *
 * 現時点では主にリクエストボディーのContent-Type指定に使用する。
 * AcceptヘッダーやレスポンスContent-Type判定など、メディアタイプを扱う用途にも再利用できる。
 */
enum ContentType : string implements AcceptValue {

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

    /** multipartフォームデータ (`multipart/form-data`) */
    case MultipartFormData = 'multipart/form-data';

    /**
     * Content-Typeヘッダーを配列またはcURL用のヘッダー文字列として取得する。
     *
     * @param  boolean $returnArray trueの場合は連想配列、falseの場合は "Content-Type: ..." 形式で返す
     * @return array{ 'Content-Type': string }|string
     */
    public function getContentTypeHeader(bool $returnArray = true): array|string {
        return ($returnArray) ?
            ['Content-Type' => $this->value] :
            sprintf('Content-Type: %s', $this->value);
    }

    /**
     * QualityValueを設定したAcceptヘッダー用のタイプに変換する。
     *
     * @param  float $quality
     * @return QualifiedAcceptValue
     */
    public function withQuality(float $quality): QualifiedAcceptValue {
        return new QualifiedAcceptValue($this, $quality);
    }

    /**
     * @inheritDoc
     */
    public function toHeaderValue(): string {
        return $this->value;
    }
}
