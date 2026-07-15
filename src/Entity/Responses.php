<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use ArrayAccess;
use Countable;
use Ennacx\SimpleCurl\Exception\ImmutableCollectionException;
use Ennacx\SimpleCurl\Exception\InvalidResponseException;
use Ennacx\SimpleCurl\Exception\ResponseNotFoundException;
use IteratorAggregate;
use Traversable;

final readonly class Responses implements ArrayAccess, Countable, IteratorAggregate {

    /**
     * @param array<string, Response> $items
     */
    public function __construct(private array $items){
    }

    public function get(string $id): Response {
        return
            $this->items[$id] ??
            throw new ResponseNotFoundException(sprintf('Response not found for request ID "%s".', $id));
    }

    public function find(string $id): ?Response {

        try{
            return $this->get($id);
        } catch(ResponseNotFoundException){
            return null;
        }
    }

    public function has(string $id): bool {
        return array_key_exists($id, $this->items);
    }

    public function all(): array {
        return $this->items;
    }

    public function offsetExists(mixed $offset): bool {
        return (is_string($offset) && $this->has($offset));
    }

    public function offsetGet(mixed $offset): Response {

        if(!is_string($offset)){
            throw new InvalidResponseException('Key must be a string');
        }

        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        throw new ImmutableCollectionException('Responses are immutable');
    }

    public function offsetUnset(mixed $offset): void {
        throw new ImmutableCollectionException('Responses are immutable');
    }

    public function count(): int {
        return count($this->items);
    }

    public function getIterator(): Traversable {
        yield from $this->items;
    }
}