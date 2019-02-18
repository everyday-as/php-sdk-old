<?php

namespace GmodStore\API;

use ArrayAccess;
use Exception;
use JsonSerializable;

abstract class Model implements ArrayAccess, JsonSerializable
{
    /**
     * Tells if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * UNIX epoch timestamp when the model was last retrieved.
     *
     * @var int
     */
    public $lastAttempted;

    /**
     * The endpoint name for the model
     *
     * @var string
     */
    public static $endpoint = '';

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
     * Array of ?with relations currently loaded for this resource
     *
     * @var array
     */
    protected $withRelations = [];

    /**
     * Valid array of relations including ?with and sub endpoints
     *
     * @var array
     */
    protected static $validRelations = [];

    /**
     * Valid array of ?with relations for a resource
     *
     * @var array
     */
    protected static $validWithRelations = [];

    /**
     * Full possible list of ?with relations that can be requested on
     * the resource and its related resources.
     *
     * @var array
     */
    protected static $generatedWithRelations = [];

    /**
     * Array of relation -> model class
     *
     * @var array
     */
    protected static $modelRelations = [];


    /**
     * Model constructor.
     *
     * @param array|\GmodStore\API\Collection $attributes
     * @param \GmodStore\API\Client|null      $client
     */
    public function __construct($attributes = [], Client $client = null)
    {
        if (!$attributes instanceof Collection) {
            $attributes = new Collection($attributes);
        }

        static::$validRelations = \array_values(\array_unique(\array_merge(static::$validRelations, static::$validWithRelations, \array_keys(self::$modelRelations))));
        static::$generatedWithRelations = self::getFullWithRelations();

        if ($client) {
            $this->setClient($client);
        }

        $this->attributes = $attributes;
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === 0 && method_exists($this->client->getClientVersion(), ($callMethod = substr_replace($name, (new \ReflectionClass($this))->getShortName(), 3, 0)))) {
            $relation = strtolower(substr($name, 3));

            \call_user_func_array([$this->client, 'with'], $this->withRelations);

            $value = call_user_func_array([$this->client, $callMethod], empty($arguments) ? ([$this] ?? []) : $arguments);

            if (!empty($value)) {
                $this->setRelation($relation, $value);
            }
        }

        return $value ?? null;
    }

    public function __isset($name)
    {
        return isset($this->attributes[$name]) || isset($this->relations[$name]);
    }

    public function __get($name)
    {
        return $this->attributes->get($name) ?? $this->getRelation($name);
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
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        if (\is_null($offset)) {
            throw new Exception('$offset must be a valid key.');
        }

        $this->attributes[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
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
    public function toJson()
    {
        return \call_user_func_array('json_encode', \array_merge([$this->toArray()], \func_get_args()));
    }

    /**
     * Set the Client to use for the model
     *
     * @param \GmodStore\API\Client $client
     *
     * @return $this
     */
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
     * Remove the Client instance where it's not needed
     *
     * @return $this
     */
    public function removeClient()
    {
        $this->client = null;

        return $this;
    }

    /**
     * Force a model's existance state to true
     * (in the case it was retrieved via a relation).
     *
     * @return $this
     */
    public function forceExists()
    {
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

        if (\count($relations) === 0) {
            $relations = $this->getRelations();
        }

        $length = \count($relations);

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
     * @throws Exception
     */
    public function fixRelations()
    {
        if (\count($relations = \array_intersect(static::$generatedWithRelations, \array_keys($this->attributes->toArray()))) > 0) {
            $matchingWith = [];
            foreach ($relations as $relation) {
                $this->setRelation($relation, $this->attributes[$relation]);
                unset($this->attributes[$relation]);

                if (\in_array($relation, static::$validWithRelations)) {
                    $matchingWith[] = $relation;
                }
            }

            \call_user_func_array([$this, 'setWith'], $matchingWith);
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
        if ($relations !== $this->withRelations) {
            \call_user_func_array([$this, 'setWith'], $relations);
        }

        \call_user_func_array([$this->client, 'with'], $this->withRelations);

        return $this;
    }

    public function setWith(...$relations)
    {
        if (\is_array($relations[0])) {
            $relations = $relations[0];
        }

        if (!empty($relations)) {
            $relations = \array_unique(\array_filter($relations));
            $validWith = static::$generatedWithRelations;

            $relations = \array_values(\array_unique(\array_intersect($validWith, $relations)));

            if (\count($diff = \array_values(\array_diff($relations, $validWith))) > 0) {
                throw new \InvalidArgumentException('?with parameters given are not valid: "'.\implode(',', $diff).'"');
            }
        }

        $this->withRelations = $relations;

        return $this;
    }

    public function fresh()
    {
        $this->lastAttempted = \time();

        \call_user_func_array([$this, 'with'], $this->withRelations);

        $this->attributes = \call_user_func_array([$this->client, static::$endpoint], [$this])->get();

        return $this->fixRelations();
    }

    public function refresh()
    {
        return $this->fresh();
    }

    /**
     * Check if the model was recently retrieved from the API
     * 5 mins or less is considered recent
     *
     * @return bool
     */
    public function recentlyAttempted()
    {
        return (\time() - $this->lastAttempted) <= 300;
    }

    public function exists()
    {
        return $this->exists;
    }

    public static function getFullWithRelations()
    {
        $validWith = static::$validWithRelations;

        foreach (static::$modelRelations as $relation => $class) {
            $validWith = \array_merge($validWith, $class::getFullWithRelations());
        }

        return \array_unique($validWith);
    }
}
