<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Response;

use ArrayAccess;
use Countable;
use Ennacx\SimpleCurl\Exception\ImmutableCollectionException;
use Ennacx\SimpleCurl\Exception\InvalidResponseException;
use Ennacx\SimpleCurl\Exception\ResponseNotFoundException;
use IteratorAggregate;
use Traversable;

/**
 * Collection of responses keyed by request ID.
 *
 * @implements ArrayAccess<string, Response>
 * @implements IteratorAggregate<string, Response>
 */
final readonly class Responses implements ArrayAccess, Countable, IteratorAggregate {

    /**
     * @var array<string, Response> $items
     */
    private array $items;

    /**
     * Creates a responses collection.
     *
     * @param  array<string, Response> $items Responses keyed by request ID.
     * @throws InvalidResponseException
     */
    public function __construct(array $items){

        foreach($items as $id => $response){
            if(!is_string($id)){
                throw new InvalidResponseException('Response collection keys must be strings.');
            }

            if(!($response instanceof Response)){
                throw new InvalidResponseException('Response collection values must be Response instances.');
            }
        }

        $this->items = $items;
    }

    /**
     * Returns a response by request ID.
     *
     * @param  string $id Request ID.
     * @throws ResponseNotFoundException
     */
    public function get(string $id): Response {
        return
            $this->items[$id] ??
            throw new ResponseNotFoundException(sprintf('Response not found for request ID "%s".', $id));
    }

    /**
     * Finds a response by request ID.
     *
     * @param string $id Request ID.
     */
    public function find(string $id): ?Response {

        try{
            return $this->get($id);
        } catch(ResponseNotFoundException){
            return null;
        }
    }

    /**
     * Checks whether a response exists for the request ID.
     *
     * @param string $id Request ID.
     */
    public function has(string $id): bool {
        return array_key_exists($id, $this->items);
    }

    /**
     * Returns all responses.
     *
     * @return array<string, Response>
     */
    public function all(): array {
        return $this->items;
    }

    /**
     * Returns a new collection containing only responses accepted by the callback.
     *
     * The callback receives the response and its request ID.
     *
     * @param callable(Response, string): bool $callback Filter callback.
     */
    public function filter(callable $callback): self {
        return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool {
        return (is_string($offset) && $this->has($offset));
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidResponseException
     * @throws ResponseNotFoundException
     */
    public function offsetGet(mixed $offset): Response {

        if(!is_string($offset)){
            throw new InvalidResponseException('Key must be a string');
        }

        return $this->get($offset);
    }

    /**
     * @inheritDoc
     *
     * @throws ImmutableCollectionException
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        throw new ImmutableCollectionException('Responses are immutable');
    }

    /**
     * @inheritDoc
     *
     * @throws ImmutableCollectionException
     */
    public function offsetUnset(mixed $offset): void {
        throw new ImmutableCollectionException('Responses are immutable');
    }

    /**
     * @inheritDoc
     */
    public function count(): int {
        return count($this->items);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable {
        yield from $this->items;
    }
}
