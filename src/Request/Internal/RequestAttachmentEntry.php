<?php
declare(strict_types=1);

/**
 * @internal
 */
namespace Ennacx\SimpleCurl\Request\Internal;

use Ennacx\SimpleCurl\Request\RequestAttachment;

/**
 * @internal
 * 添付ファイルと同名フィールド上書き設定をまとめる値オブジェクト。
 */
final readonly class RequestAttachmentEntry {

    /**
     * コンストラクタ
     *
     * @param RequestAttachment $attachment     添付ファイル情報
     * @param boolean           $allowOverwrite multipartフィールド名が重複した場合に上書きするかどうか
     */
    public function __construct(
        public RequestAttachment $attachment,
        public bool              $allowOverwrite = true
    ){
    }
}
