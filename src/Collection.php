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
use function call_user_func;
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

    function toArray(): array
    {
        $array = [];

        foreach ($this->attributes as $index => $item) {
            $array[$index] = $item instanceof self ? $item->toArray() : $item;
        }

        return $array;
    }

    public function toJson()
    {
        $json = call_user_func('json_encode', array_merge([$this->toArray()], func_get_args()));

        return $json !== false ? $json : null;
    }

    /*** ArrayAccess ***/

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->attributes[$offset] ?? null;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
        $this->updateKeys();
    }

    /*** Countable ***/

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->attributes);
    }

    /*** JsonSerializable ***/

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return $this->toJson();
    }

    /*** SeekableIterator, Iterator ***/

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        if (is_null($this->position)) {
            $this->updateKeys();
            $this->position = $this->keys[0];
        }

        return $this->offsetGet($this->position);
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        $next = array_search($this->position, $this->keys) + 1;

        $this->position = $this->keys[$next] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return $this->offsetExists($this->position);
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->position = $this->keys[0];
    }

    /**
     * {@inheritDoc}
     */
    public function seek($position)
    {
        if (!$this->offsetExists($position)) {
            throw new OutOfBoundsException('`'.$position.'` invalid seek position');
        }

        $this->position = $position;
    }

    protected function updateKeys()
    {
        $this->keys = array_keys($this->attributes);
    }
}
