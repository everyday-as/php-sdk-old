<?php

namespace GmodStore\API;

use Exception;
use GmodStore\API\Exceptions\EndpointException;
use GmodStore\API\Interfaces\ModelInterface;
use InvalidArgumentException;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_unshift;
use function call_user_func_array;
use function in_array;
use function lcfirst;
use function method_exists;
use function strlen;
use function substr;
use function time;

abstract class Model extends Collection implements ModelInterface
{
    /**
     * Valid array of relations that are retrieved from sub endpoints.
     *
     * @var array
     */
    public static $validRelations = [];

    /**
     * Valid array of ?with relations for a model.
     *
     * @var array
     */
    public static $validWithRelations = [];

    /**
     * Mapping of relation -> model class.
     *
     * @var array
     */
    public static $modelRelations = [];

    /**
     * Automatic list of ALL possible relation names: ?with and sub endpoints.
     *
     * @var array
     */
    public static $generatedRelations = [];

    /**
     * @var \GmodStore\API\Endpoint
     */
    protected static $endpoint;

    /**
     * Epoch timestamp of when the model was last retrieved/attempted.
     *
     * @var int
     */
    public $lastAttempted;

    /**
     * Loaded relationships for the model.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * Array of ?with relations currently loaded for this model.
     *
     * @var array
     */
    protected $withRelations = [];

    /**
     * Model constructor.
     *
     * @param array                        $attributes
     * @param \GmodStore\API\Endpoint|null $endpoint
     */
    public function __construct($attributes = [], Endpoint $endpoint = null)
    {
        parent::__construct($attributes);

        if ($endpoint) {
            static::$endpoint = $endpoint;
        }

        static::boot();

        $this->relations = new Collection();
        $this->fixRelations();
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     * @throws \Exception
     *
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) !== 'get' || strlen($name) === 3) {
            throw new InvalidArgumentException('`'.$name.'` is an invalid method.');
        }

        $relation = lcfirst(substr($name, 3));

        if (empty(static::$endpoint)) {
            throw new Exception('No endpoint instance has been set on this model.');
        }

        if (!in_array($relation, static::$validRelations)) {
            throw new InvalidArgumentException('`'.$name.'` is not a valid relation on: '.static::class.'.');
        }

        if (!method_exists(static::$endpoint, $name)) {
            throw new EndpointException('`'.$name.'` method does not exist on endpoint.');
        }

        array_unshift($arguments, $this->attributes['id']);

        $result = call_user_func_array([static::$endpoint, $name], $arguments);

        $this->setRelation($relation, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), $this->relations->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $return = parent::offsetGet($offset);

        if (empty($parent)) {
            $return = $this->getRelation($offset);
        }

        return $return;
    }

    /**
     * Boot the model.
     */
    public static function boot()
    {
        static::$generatedRelations = array_unique(array_merge(static::$validRelations, static::$validWithRelations, array_keys(static::$modelRelations)));
    }

    /**
     * @return \GmodStore\API\Endpoint
     */
    public static function getEndpoint(): Endpoint
    {
        return self::$endpoint;
    }

    /**
     * Set proper relations and instantiate where needed.
     *
     * @return $this
     */
    public function fixRelations()
    {
        $allRelationNames = self::$generatedRelations;
        $this->attributes = $mergedData = array_merge($this->attributes, $this->relations->toArray());
        $this->relations = new Collection();

        foreach ($mergedData as $relation => $value) {
            if (!in_array($relation, $allRelationNames)) {
                continue;
            }

            if (in_array($relation, static::$validRelations) || in_array($relation, static::$validWithRelations)) {
                if (in_array($relation, static::$validWithRelations)) {
                    $this->withRelations[] = $relation;
                }

                $this->relations[$relation] = $value;
            }
            if (isset(static::$modelRelations[$relation])) {
                if (!$value instanceof self) {
                    $value = new static::$modelRelations[$relation]($value, static::$endpoint);
                }

                $this->relations[$relation] = $value;
            }

            unset($this->attributes[$relation]);
        }

        return $this;
    }

    /**
     * Sets a relation.
     *
     * @param $name
     * @param $data
     *
     * @return static
     */
    public function setRelation($name, $data): self
    {
        $this->relations[$name] = $data;

        return $this;
    }

    /**
     * Does this relation exist on the model?
     *
     * @param $name
     *
     * @return bool
     */
    public function hasRelation($name)
    {
        return isset($this->relations[$name]);
    }

    /**
     * Retrieve a **loaded** relation.
     *
     * @param $name
     *
     * @return mixed|null
     */
    public function getRelation($name)
    {
        return $this->hasRelation($name) ? $this->relations[$name] : null;
    }

    /**
     * Check if the model was "recently" retrieved from the API based off of
     * difference provided.
     *
     * @param int $difference
     *
     * @return bool
     */
    public function recentlyAttempted($difference = 300): bool
    {
        return (time() - $this->lastAttempted) <= $difference;
    }

    /**
     * Get array of loaded relations as key => value.
     *
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * Get array of loaded relation names only.
     *
     * @return array
     */
    public function getRelationNames(): array
    {
        return array_keys($this->relations);
    }
}
