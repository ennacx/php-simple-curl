<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Exception\InvalidRequestException;

/**
 * Accept header value with a quality value.
 */
final readonly class QualifiedAcceptValue implements AcceptValueInterface {

    /**
     * Creates a qualified Accept header value.
     *
     * @param  AcceptValueInterface|string $value   Media type, media range, or AcceptValueInterface.
     * @param  float                       $quality Quality value between 0.0 and 1.0.
     * @throws InvalidRequestException
     */
    public function __construct(
        private AcceptValueInterface|string $value,
        private float                       $quality,
    ){
        if(is_string($value) && trim($value) === ''){
            throw new InvalidRequestException('Accept value must not be empty.');
        }

        // 二重ラップと文字列指定時のq値二重指定を防ぐ
        if($value instanceof self || (is_string($value) && preg_match('/(?:^|;)\s*q\s*=/i', $value) === 1)){
            throw new InvalidRequestException('Qualified accept value cannot be qualified again.');
        }

        if($quality < 0.0 || $quality > 1.0){
            throw new InvalidRequestException('Accept quality value must be between 0.0 and 1.0.');
        }
    }

    /**
     * Converts the value into an Accept header segment.
     */
    public function toHeaderValue(): string {
        $value = ($this->value instanceof AcceptValueInterface) ? $this->value->toHeaderValue() : trim($this->value);

        return sprintf('%s;q=%s', $value, self::formatQuality($this->quality));
    }

    /**
     * q値は最大3桁まで出力し、不要な末尾の0を落とす
     */
    private static function formatQuality(float $quality): string {
        return rtrim(rtrim(number_format($quality, 3, '.', ''), '0'), '.');
    }
}
