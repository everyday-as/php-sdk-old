<?php

namespace kanalumaddela\GmodStoreAPI;

use Exception;
use JsonSerializable;
use ReflectionException;
use ReflectionMethod;

abstract class Model implements JsonSerializable
{
    /**
     * Tells if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Tells if the model was recently retrieved from the API.
     *
     * @var bool
     */
    public $recentlyAttempted = false;

    /**
     * Timestamp when the model was last retrieved.
     *
     * @var int
     */
    public $lastAttempted;

    /**
     * Endpoint name.
     *
     * @var string
     */
    public static $endpoint = 'test';

    /**
     * API client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Attributes of the model.
     *
     * @var Collection
     */
    protected $attributes = [];

    /**
     * Loaded relationships for the model.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * List of relating models that will be instantiad to their respactive classes.
     *
     * @var array
     */
    protected static $modelRelations = [];

    /**
     * Relations originating from the ?with query param.
     *
     * @var array
     */
    protected $withRelations = [];

    public function __construct($attributes = [])
    {
        if (\is_string($attributes) || \is_int($attributes)) {
            $attributes = ['id' => $attributes];
        }
        if (\is_object($attributes)) {
            if (!$attributes instanceof Collection) {
                $attributes = new Collection($attributes);
            }
        }

        $this->attributes = $attributes;
    }

    public function __isset($name)
    {
        return isset($this->attributes[$name]) || isset($this->relations[$name]);
    }

    public function __get($name)
    {
        if (!$this->relationLoaded($name) && \method_exists($this, 'get'.\ucfirst($name))) {
            try {
                \call_user_func_array([$this, 'get'.\ucfirst($name)], []);
                /*
                $reflectionMethod = new ReflectionMethod(static::class, 'get' . \ucfirst($name));

                if ($reflectionMethod->getNumberOfRequiredParameters() === 0) {
                    \call_user_func_array([$this, 'get' . \ucfirst($name)], []);
                }
                */
            } catch (ReflectionException $e) {
            }
        }

        return isset($this->attributes[$name]) ? $this->attributes[$name] : $this->getRelation($name);
    }

    /**
     * Returns toJson().
     *
     * @return false|string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Returns the attributes of the model.
     *
     * @return array
     */
    public function toArray()
    {
        return \array_merge($this->attributes->toArray(), $this->relations);
    }

    /**
     * Returns json encoded string of model attributes.
     *
     * @return false|string
     */
    public function toJson($options = 0, $depth = 512)
    {
        return \call_user_func_array('json_encode', [$this->toArray(), $options, $depth]);
    }

    public function setClient(Client $client)
    {
        $this->client = $client;

        if (isset($this->attributes['id']) && \count($this->attributes) === 1) {
            $this->fresh();
        }

        return $this;
    }

    /**
     * Return the Client instance.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Force a model's existance state to true
     * (in the case it was retrieved via a relation).
     *
     * @return $this
     */
    public function forceExists()
    {
        $this->recentlyAttempted = true;
        $this->lastAttempted = \time();
        $this->exists = true;

        return $this;
    }

    /**
     * Checks if a relation is loaded.
     *
     * @param $name
     *
     * @return bool
     */
    public function relationLoaded($name)
    {
        return isset($this->relations[$name]);
    }

    /**
     * Returns a relation or null.
     *
     * @param $name
     *
     * @return mixed|null
     */
    public function getRelation($name)
    {
        return $this->relationLoaded($name) ? $this->relations[$name] : null;
    }

    /**
     * Sets a relation.
     *
     * @param $name
     * @param $data
     *
     * @return $this
     */
    public function setRelation($name, $data)
    {
        $this->relations[$name] = $data;

        return $this;
    }

    /**
     * Remove all or certain relations from a model.
     *
     * @param mixed ...$relations
     *
     * @return $this
     */
    public function removeRelations(...$relations)
    {
        if (isset($relations[0]) && \is_array($relations[0])) {
            $relations = $relations[0];
        }

        if (count($relations) === 0) {
            $relations = $this->getRelations();
        }

        $length = count($relations);

        for ($i = 0; $i < $length; $i++) {
            $relation = $relations[$i];

            if (isset($this->relations[$relation])) {
                unset($this->relations[$relation]);
            }
        }

        return $this;
    }

    /**
     * Return array of loaded relations.
     *
     * @return array
     */
    public function getRelations()
    {
        return \array_keys($this->relations);
    }

    /**
     * Set proper relations and instantiate where needed.
     *
     * @return $this
     */
    public function fixRelations()
    {
        $allRelations = \array_unique(\array_merge($this->getRelations(), $this->withRelations));

        if (($length = \count($allRelations)) > 0) {
            for ($i = 0; $i < $length; $i++) {
                $relation = $allRelations[$i];

                if (isset($this->attributes[$relation])) {
                    $this->setRelation($relation, (object) $this->attributes[$relation]);
                    unset($this->attributes[$relation]);
                }

                if ($this->relationLoaded($relation)) {
                    if (isset(static::$modelRelations[$relation])) {
                        $data = (new static::$modelRelations[$relation]($this->getRelation($relation)))->setClient($this->client)->with($allRelations)->forceExists();
                        $this->setRelation($relation, $data);
                    }
                } else {
                    throw new Exception("Relation '{$relation}' not found. Make sure the relation exists at this endpoint.");
                }
            }
        }

        return $this;
    }

    /**
     * Sets the with() relations for the API.
     *
     * @param mixed ...$relations
     *
     * @return $this
     */
    public function with(...$relations)
    {
        \call_user_func_array([$this->client, 'with'], $relations);

        $this->withRelations = $this->client->getWith();

        return $this;
    }

    public function fresh()
    {
        if (empty(static::$endpoint)) {
            static::$endpoint = self::$endpoint;
        }

        $this->withRelations = \array_unique(\array_merge($this->client->getWith(), $this->withRelations));

        $data = $this->newRequest()->get();

        $this->attributes = !\is_null($data) ? $data : [];

        //$this->attributes = $this->client->with($this->withRelations)->{static::$endpoint}()->set($this->id)->get(true);
        $this->exists = !\is_null($data);

        $this->recentlyAttempted = true;
        $this->lastAttempted = \time();

        $this->fixRelations();

        return $this;
    }

    public function refresh()
    {
        return $this->fresh();
    }

    public function recentlyAttempted()
    {
        return $this->recentlyAttempted;
    }

    public function exists()
    {
        return $this->exists;
    }

    /**
     * Sets up the client with the appropriate endpoints and relations.
     *
     * @return Client
     */
    protected function newRequest()
    {
        return $this->client->{static::$endpoint}(isset($this->id) ? $this->id : null)->with($this->withRelations);
    }
}
