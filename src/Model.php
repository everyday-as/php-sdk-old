<?php

namespace GmodStore\API;

use InvalidArgumentException;
use ReflectionClass;
use function array_diff;
use function array_intersect;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function call_user_func_array;
use function count;
use function implode;
use function in_array;
use function is_array;
use function method_exists;
use function strpos;
use function strtolower;
use function substr;
use function time;

abstract class Model extends Collection
{
    /**
     * Determine if model class was booted.
     *
     * @var bool
     */
    public static $booted = false;
    /**
     * List of booted models.
     *
     * @var array
     */
    protected static $bootedModels = [];
    /**
     * Full possible list of ?with relations that can be requested on
     * the resource and its related resources.
     *
     * @var array
     */
    protected static $generatedWithRelations = [];
    /**
     * Valid array of relations that are sub endpoints.
     *
     * @var array
     */
    protected static $validRelations = [];
    /**
     * Valid array of ?with relations for a resource.
     *
     * @var array
     */
    protected static $validWithRelations = [];
    /**
     * Array of relation -> model class.
     *
     * @var array
     */
    protected static $modelRelations = [];
    /**
     * @var bool
     */
    public $exists = false;
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
     * Array of ?with relations currently loaded for this resource.
     *
     * @var array
     */
    protected $withRelations = [];
    /**
     * API client.
     *
     * @var \GmodStore\API\Client
     */
    protected $client;

    public function __construct($attributes = [], Client $client = null)
    {
        parent::__construct($attributes);

        if ($client) {
            $this->setClient($client);
        }

        if (!in_array(static::class, static::$bootedModels)) {
            static::boot();
        }
    }

    /**
     * Boot the model.
     */
    public static function boot()
    {
        static::$booted = true;

        static::$validRelations = array_values(array_unique(array_merge(static::$validRelations, static::$validWithRelations, array_keys(self::$modelRelations))));
        static::$generatedWithRelations = self::getFullWithRelations();
    }

    /**
     * Get the full list of possible relations, both ?with relations and sub endpoints.
     *
     * @return array
     */
    public static function getFullWithRelations(): array
    {
        $validWith = static::$validWithRelations;

        foreach (static::$modelRelations as $relation => $class) {
            $validWith = array_merge($validWith, $class::getFullWithRelations());
        }

        return array_unique($validWith);
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === 0 && method_exists($this->client->getClientVersion(), ($callMethod = substr_replace($name, (new ReflectionClass(static::class))->getShortName(), 3, 0)))) {
            $relation = strtolower(substr($name, 3));

            call_user_func_array([$this->client, 'with'], $this->withRelations);

            $value = call_user_func_array([$this->client, $callMethod], empty($arguments) ? ([$this] ?? []) : $arguments);

            if (!empty($value)) {
                $this->setRelation($relation, $value);
            }

            return $value ?? null;
        }
    }

    /**
     * Sets a relation.
     *
     * @param $name
     * @param $data
     *
     * @return $this
     */
    public function setRelation($name, $data): self
    {
        $this->relations[$name] = $data;

        return $this;
    }

    /**
     * Return the Client instance.
     *
     * @return \GmodStore\API\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Set the Client to use for the model.
     *
     * @param \GmodStore\API\Client $client
     *
     * @return static
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Remove the Client instance on the model.
     *
     * @return static
     */
    public function removeClient(): self
    {
        $this->client = null;

        return $this;
    }

    /**
     * Force a model's existence.
     *
     * @return static
     */
    public function forceExists(): self
    {
        $this->exists = true;

        return $this;
    }

    /**
     * Check if the model was recently retrieved from the API.
     * "Recent" being 5 mins or less.
     *
     * @return bool
     */
    public function recentlyAttempted()
    {
        return (time() - $this->lastAttempted) <= 300;
    }

    /**
     * Set proper relations and instantiate where needed.
     *
     * @return $this
     */
    public function fixRelations()
    {
        if (count($relations = array_intersect(static::$generatedWithRelations, array_keys($this->toArray()))) > 0) {
            $matchingWith = [];
            foreach ($relations as $relation) {
                if (isset(static::$modelRelations[$relation]) && !$this[$relation] instanceof static::$modelRelations[$relation]) {
                    $this[$relation] = new static::$modelRelations[$relation]($this[$relation]);
                }

                $this->setRelation($relation, $this[$relation]);
                unset($this[$relation]);

                if (in_array($relation, static::$validWithRelations)) {
                    $matchingWith[] = $relation;
                }
            }

            call_user_func_array([$this, 'setWith'], $matchingWith);
        }

        return $this;
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

    /**
     * @param string $relation
     *
     * @return bool
     */
    public function isLoaded($relation): bool
    {
        return isset($this->relations[$relation]);
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
            call_user_func_array([$this, 'setWith'], $relations);
        }

        call_user_func_array([$this->client, 'with'], $this->withRelations);

        return $this;
    }

    /**
     * Set the ?with relations when retrieving a model and validate them.
     *
     * @param mixed ...$relations
     *
     * @return $this
     */
    public function setWith(...$relations): self
    {
        if (is_array($relations[0])) {
            $relations = $relations[0];
        }

        if (!empty($relations)) {
            $relations = array_unique(array_filter($relations));
            $validWith = static::$generatedWithRelations;

            $relations = array_values(array_unique(array_intersect($validWith, $relations)));

            if (count($diff = array_values(array_diff($relations, $validWith))) > 0) {
                throw new InvalidArgumentException('?with parameters given are not valid: "'.implode(',', $diff).'"');
            }
        }

        $this->withRelations = $relations;

        return $this;
    }

    public function fresh(...$with)
    {
    }
}
