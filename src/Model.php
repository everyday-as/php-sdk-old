<?php

namespace GmodStore\API;

use GmodStore\API\Interfaces\ModelInterface;
use function array_keys;
use function array_merge;
use function array_unique;
use function in_array;
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
     * List of booted models.
     *
     * @var array
     */
    protected static $bootedModels = [];
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

        if (!in_array(static::class, static::$bootedModels)) {
            static::boot();
        }

        $this->relations = new Collection();
        $this->fixRelations();
    }

    /**
     * Boot the model.
     */
    public static function boot()
    {
        if (!in_array(static::class, self::$bootedModels)) {
            self::$bootedModels[] = static::class;
        }

        self::$generatedRelations = array_unique(array_merge(static::$validRelations, static::$validWithRelations, array_keys(static::$modelRelations)));
    }

    /**
     * Set proper relations and instantiate where needed.
     *
     * @return $this
     */
    public function fixRelations()
    {
        $allRelationNames = self::$generatedRelations;
        $mergedData = array_merge($this->attributes, $this->relations->toArray());
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
                if (!$value instanceof Model) {
                    $value = new static::$modelRelations[$relation]($value);
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
     * Check if the model was "recently" retrieved from the API based off of
     * difference provided
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
