<?php

namespace GmodStore\API;

use ArrayAccess;
use Countable;
use JsonSerializable;
use function call_user_func_array;
use function count;
use function is_null;
use function is_object;

class Collection implements ArrayAccess, Countable, JsonSerializable
{
    /**
     * @var array
     */
    protected $attributes;

    /**
     * Collection constructor.
     *
     * @param array|object|\GmodStore\API\Collection $attributes
     */
    public function __construct($attributes = [])
    {
        if (is_object($attributes)) {
            $attributes = $attributes instanceof self ? $attributes->toArray() : (array) $attributes;
        }

        $this->attributes = $attributes;
    }

    /**
     * Returns the attributes of the model.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @param int $options
     * @param int $depth
     *
     * @return string|null
     */
    public function toJson($options = 0, $depth = 512)
    {
        $json = call_user_func_array('json_encode', [$this->toArray(), $options, $depth]);

        return $json !== false ? $json : null;
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($name)
    {
        return $this->offsetExists($name);
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
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return (!is_null($value = $this->offsetGet($key))) ? $value : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->attributes[$offset] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Check if the Collection is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->attributes) || $this->count() === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->attributes);
    }
}
