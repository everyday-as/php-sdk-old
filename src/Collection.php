<?php

namespace GmodStore\API;

use ArrayAccess;
use Countable;
use JsonSerializable;

class Collection implements ArrayAccess, Countable, JsonSerializable
{
    /**
     * @var array
     */
    protected $attributes;

    /**
     * Collection constructor.
     *
     * @param array|object $attributes
     */
    public function __construct($attributes = [])
    {
        if (\is_object($attributes)) {
            $attributes = $attributes instanceof self ? $attributes->toArray() : (array) $attributes;
        }

        $this->attributes = $attributes;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

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
     * Returns the attributes of the model.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * @param int $options
     * @param int $depth
     *
     * @return mixed|null
     */
    public function toJson($options = 0, $depth = 512)
    {
        $json = \call_user_func_array('json_encode', [$this->toArray(), $options, $depth]);

        return $json !== false ? $json : null;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->attributes[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public function isEmpty()
    {
        return count($this->empty) || empty($this->attributes);
    }

    /**
     * Count elements of an object
     *
     * @link  https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return \count($this->attributes);
    }
}
