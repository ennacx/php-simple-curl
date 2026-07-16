<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Option;

use Countable;
use Ennacx\SimpleCurl\Config\CurlOptionsApplierInterface;
use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;
use IteratorAggregate;
use Traversable;

/**
 * Raw CURLOPT_* option set.
 *
 * Use this as an escape hatch when a cURL option is not covered by a dedicated
 * config object.
 */
final readonly class RawCurlOptions implements CurlOptionsApplierInterface, Countable, IteratorAggregate {

    /**
     * @var array<int, mixed>
     */
    private array $options;

    /**
     * @param array<int, mixed> $options
     */
    private function __construct(array $options, private bool $overwrite = true){
        $this->options = $options;
    }

    /**
     * Creates a raw cURL option set.
     *
     * @param array<int, mixed> $options   Raw cURL options. (e.g. `[CURLOPT_RETURNTRANSFER => true]`)
     * @param boolean           $overwrite Whether an existing key should be overwritten.
     * @throws InvalidConfigurationException
     */
    public static function create(array $options, bool $overwrite = true): self {

        $opts = [];

        foreach($options as $option => $value){
            if(!is_int($option)){
                throw new InvalidConfigurationException(sprintf('Option key "%s" is not an integer.', $option));
            }

            $opts[$option] = $value;
        }

        return new self($opts, $overwrite);
    }

    /**
     * Returns a new option set with the given raw cURL option.
     */
    public function with(int $option, mixed $value): self {

        $applied = $this->options;

        $applied[$option] = $value;

        return new self($applied, $this->overwrite);
    }

    /**
     * Returns a new option set without the given raw cURL option.
     */
    public function without(int $option): self {

        if(!array_key_exists($option, $this->options)){
            return $this;
        }

        $applied = $this->options;

        unset($applied[$option]);

        return new self($applied, $this->overwrite);
    }

    /**
     * Checks whether the raw cURL option exists.
     */
    public function has(int $option): bool {
        return (array_key_exists($option, $this->options));
    }

    /**
     * Returns a raw cURL option value.
     *
     * @throws InvalidConfigurationException
     */
    public function get(int $option): mixed {

        if(!$this->has($option)){
            throw new InvalidConfigurationException(sprintf('Option key "%s" does not exist in Raw Curl Options.', $option));
        }

        return $this->options[$option];
    }

    /**
     * Finds a raw cURL option value.
     */
    public function find(int $option): mixed {
        return ($this->has($option)) ? $this->options[$option] : null;
    }

    /**
     * Returns all raw cURL options.
     *
     * @return array<int, mixed>
     */
    public function all(): array {
        return $this->options;
    }

    /**
     * Checks whether existing cURL options should be overwritten when applied.
     */
    public function isOverwriteEnabled(): bool {
        return $this->overwrite;
    }

    /**
     * @inheritDoc
     */
    public function applyToCurlOptions(array &$options, array &$headers): void {

        foreach($this->options as $key => $value){
            if($this->overwrite || !array_key_exists($key, $options)){
                $options[$key] = $value;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function count(): int {
        return count($this->options);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable {
        yield from $this->options;
    }
}
