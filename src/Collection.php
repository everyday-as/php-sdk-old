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

    public function toArray(): array
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
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

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
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
        $this->updateKeys();
    }

    /*** Countable ***/

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->attributes);
    }

    /*** JsonSerializable ***/

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toJson();
    }

    /*** SeekableIterator, Iterator ***/

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
            throw new OutOfBoundsException('`'.$position.'` invalid seek position');
        }

        $this->position = $position;
    }

    protected function updateKeys()
    {
        $this->keys = array_keys($this->attributes);
    }
}
