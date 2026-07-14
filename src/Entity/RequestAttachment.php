<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

/**
 * multipart/form-dataで送信する添付ファイルを表す値オブジェクト。
 */
final readonly class RequestAttachment {

    /**
     * コンストラクタ
     *
     * @param string      $name     multipartフィールド名
     * @param string      $path     添付するローカルファイルパス
     * @param string|null $filename 送信時に使用するファイル名。nullの場合はcURLの既定に従う
     * @param string|null $mimeType 送信時に使用するMIMEタイプ。nullの場合はcURLの既定に従う
     */
    public function __construct(
        public string $name,
        public string $path,
        public ?string $filename = null,
        public ?string $mimeType = null,
    ){
    }
}
