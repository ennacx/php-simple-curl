<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Enum\ContentType;

/**
 * Requestに保持する送信ボディの内容と変換設定を表す値オブジェクト。
 */
final readonly class RequestBody {

    /**
     * コンストラクタ
     *
     * @param array|string|null $body        送信するボディ内容
     * @param ContentType       $contentType ボディのメディアタイプ
     * @param array             $options     Factoryでボディを変換する際の追加オプション
     */
    public function __construct(
        public array|string|null $body,
        public ContentType $contentType,
        public array $options = [],
    ){
    }
}
