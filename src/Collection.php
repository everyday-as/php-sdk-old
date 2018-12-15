<?php

namespace kanalumaddela\GmodStoreAPI;

use JsonSerializable;
use ArrayAccess;

class Collection implements JsonSerializable, ArrayAccess
{
    /**
     * @var array
     */
    protected $attributes;

    /**
     * Collection constructor.
     * @param array|object $attributes
     */
    public function __construct($attributes = [])
    {
        if (is_object($attributes)) {
            $attributes = (array) $attributes;
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


    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return (!is_null($value = $this->offsetGet($key))) ? $value : $default;
    }

    /**
     * Returns the attributes of the model
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
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->attributes[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        dump($offset);
        dump($value);
        die();
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }
}