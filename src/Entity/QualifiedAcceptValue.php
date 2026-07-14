<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use InvalidArgumentException;

/**
 * QualityValue付きのAcceptヘッダー値を表す値オブジェクト。
 */
final readonly class QualifiedAcceptValue implements AcceptValue {

    /**
     * コンストラクタ
     *
     * @param AcceptValue|string $value   メディアタイプまたはメディアレンジ
     * @param float              $quality Quality Value。0.0から1.0まで
     */
    public function __construct(
        private AcceptValue|string $value,
        private float              $quality
    ){
        if(is_string($value) && trim($value) === ''){
            throw new InvalidArgumentException('Accept value must not be empty.');
        }

        // 二重ラップ防止と文字列指定時に`q=`があるかの簡易チェック (元々あったq値のチェックまではしない)
        if($value instanceof self || (is_string($value) && preg_match('/(?:^|;)\s*q\s*=/i', $value) === 1)){
            throw new InvalidArgumentException('Qualified accept value cannot be qualified again.');
        }

        if($quality < 0.0 || $quality > 1.0){
            throw new InvalidArgumentException('Quality value must be between 0.0 and 1.0.');
        }
    }

    /**
     * @inheritDoc
     */
    public function toHeaderValue(): string {

        $value = ($this->value instanceof AcceptValue) ? $this->value->toHeaderValue() : trim($this->value);

        return sprintf('%s;q=%s', $value, self::formatQuality($this->quality),);
    }

    /**
     * Quality ValueをHTTPヘッダー用の最大3桁小数へ整形する。
     *
     * @param  float $quality
     * @return string
     */
    private static function formatQuality(float $quality): string {
        return rtrim(rtrim(number_format($quality, 3, '.', ''), '0'), '.');
    }
}
