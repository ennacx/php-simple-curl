<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

/**
 * 添付ファイルと同名フィールド上書き設定をまとめる値オブジェクト。
 */
final readonly class RequestAttachmentEntry {

    /**
     * コンストラクタ
     *
     * @param RequestAttachment $attachment 添付ファイル情報
     * @param boolean           $overwrite  multipartフィールド名が重複した場合に上書きするかどうか
     */
    public function __construct(
        public RequestAttachment $attachment,
        public bool $overwrite = true
    ){
    }
}
