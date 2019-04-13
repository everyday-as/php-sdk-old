<?php

namespace GmodStore\API;

use ArrayAccess;
use Countable;
use JsonSerializable;
use OutOfBoundsException;
use SeekableIterator;
use function array_keys;
use function array_merge;
use function array_search;
use function count;
use function func_get_args;
use function is_null;

class Collection implements ArrayAccess, Countable, JsonSerializable, SeekableIterator
{
    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var array
     */
    protected $keys = [];

    /**
     * @var int|string
     */
    protected $position;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * Is the Collection empty?
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->attributes) === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (empty($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }

        $this->updateKeys();
    }

    /**
     * Update the list of keys in $this->attributes.
     */
    protected function updateKeys()
    {
        $this->keys = array_keys($this->attributes);
    }

    /*** ArrayAccess ***/

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
        $this->updateKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toJson();
    }

    /**
     * Convert attributes to json string.
     *
     * @return string|null
     */
    public function toJson(): ?string
    {
        $json = call_user_func_array('json_encode', array_merge([$this->toArray()], func_get_args()));

        return $json !== false ? $json : null;
    }

    /*** Countable ***/

    /**
     * Get the attributes and its elements as an array only.
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [];

        foreach ($this->attributes as $index => $item) {
            $array[$index] = $item instanceof self ? $item->toArray() : $item;
        }

        return $array;
    }

    /*** JsonSerializable ***/

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if (is_null($this->position)) {
            $this->updateKeys();
            $this->position = $this->keys[0];
        }

        return $this->offsetGet($this->position);
    }

    /*** SeekableIterator, Iterator ***/

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->attributes[$offset] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $next = array_search($this->position, $this->keys) + 1;

        $this->position = $this->keys[$next] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->offsetExists($this->position);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = $this->keys[0];
    }

    /**
     * {@inheritdoc}
     */
    public function seek($position)
    {
        if (!$this->offsetExists($position)) {
            throw new OutOfBoundsException('` Invalid seek position: `'.$position.'`');
        }

        $this->position = $position;
    }
}
