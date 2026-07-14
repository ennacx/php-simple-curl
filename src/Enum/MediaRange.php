<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

use Ennacx\SimpleCurl\Entity\AcceptValue;
use Ennacx\SimpleCurl\Entity\QualifiedAcceptValue;

/**
 * Acceptヘッダーで使用するメディアレンジを表す列挙型。
 */
enum MediaRange : string implements AcceptValue {

    /** すべて */
    case Any = '*/*';

    /** テキスト */
    case Text = 'text/*';

    /** 画像 */
    case Image = 'image/*';

    /** 動画 */
    case Video = 'video/*';

    /** アプリケーション */
    case Application = 'application/*';

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
