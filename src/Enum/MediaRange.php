<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

use Ennacx\SimpleCurl\Request\AcceptValueInterface;
use Ennacx\SimpleCurl\Request\QualifiedAcceptValue;

/**
 * Common Accept header media ranges.
 */
enum MediaRange : string implements AcceptValueInterface {

    /** Any media type. */
    case Any = '*/*';

    /** Any text media type. */
    case Text = 'text/*';

    /** Any image media type. */
    case Image = 'image/*';

    /** Any video media type. */
    case Video = 'video/*';

    /** Any application media type. */
    case Application = 'application/*';

    /**
     * Returns this media range with an Accept quality value.
     *
     * @param float $quality Quality value between 0.0 and 1.0.
     */
    public function withQuality(float $quality): QualifiedAcceptValue {
        return QualifiedAcceptValue::create($this, $quality);
    }

    /**
     * @inheritDoc
     */
    public function toHeaderValue(): string {
        return $this->value;
    }
}
