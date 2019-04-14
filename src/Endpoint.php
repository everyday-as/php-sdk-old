<?php

namespace GmodStore\API;

use GmodStore\API\Exceptions\EndpointException;
use GmodStore\API\Interfaces\EndpointInterface;
use GuzzleHttp\Exception\ClientException;
use InvalidArgumentException;
use function array_unshift;
use function class_exists;
use function implode;
use function json_decode;

abstract class Endpoint implements EndpointInterface
{
    /**
     * @var string
     */
    public static $endpointPath;

    /**
     * @var \GmodStore\API\Interfaces\ModelInterface
     */
    public static $model;

    /**
     * Sub endpoints mappings and the models they use.
     *
     * @var array
     */
    public static $endpoints = [];

    /**
     * @var \GmodStore\API\Interfaces\ModelInterface
     */
    protected $currentModel;

    /**
     * URL parameters for the current model.
     *
     * @var array
     */
    protected $endpointParameters = [];

    /**
     * @var int
     */
    protected $id;

    /**
     * @var \GmodStore\API\Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $clientWith = [];

    /**
     * Endpoint constructor.
     *
     * @param \GmodStore\API\Client $client
     * @param int|null              $id
     */
    public function __construct(Client $client, $id = null)
    {
        $this->client = $client;

        if ($id) {
            $this->setId($id);
        }

        $this->currentModel = static::$model;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @throws \GmodStore\API\Exceptions\EndpointException
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (static::hasEndpoint($name)) {
            $this->endpointParameters[] = $name;

            if (!empty($arguments)) {
                $this->endpointParameters[] = $arguments[0];
            }

            $this->currentModel = static::$endpoints[$name];

            return $this;
        }

        throw new EndpointException('`'.$name.'` is not a valid method.');
    }

    public static function hasEndpoint($endpoint)
    {
        return isset(static::$endpoints[$endpoint]);
    }

    public static function getEndpointModel($endpoint)
    {
        if (!static::hasEndpoint($endpoint)) {
            throw new InvalidArgumentException('`'.$endpoint.'` mapping does not exist.');
        }

        return static::$endpoints[$endpoint];
    }

    /**
     * @param $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        if (!empty($this->endpointParameters)) {
            array_unshift($this->endpointParameters, $id);
        } else {
            $this->endpointParameters = [$id];
            $this->currentModel = static::$model;
        }

        return $this;
    }

    public function with(...$with)
    {
        $this->clientWith = $with;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \GmodStore\API\Exceptions\EndpointException
     */
    public function get($id = null)
    {
        $this->client->setRequestMethod('GET');

        if ($id) {
            $this->setId($id);
        }

        $response = $this->send();
        $response = $response->getBody()->getContents();

        // reset
        $this->id = null;
        $this->clientWith = $this->endpointParameters = [];

        return json_decode($response, true);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \GmodStore\API\Exceptions\EndpointException
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface|null
     */
    protected function send()
    {
        try {
            $response = $this->client->setEndpoint($this->buildUrlPath())->setWith($this->clientWith)->send();
        } catch (ClientException $e) {
            $response = null;

            throw new EndpointException('Request failed: '.$e->getMessage());
        }

        return $response;
    }

    /**
     * @return array
     */
    public function getEndpointParameters(): array
    {
        return $this->endpointParameters;
    }

    /**
     * @param array $endpointParameters
     *
     * @return \GmodStore\API\Endpoint
     */
    public function setEndpointParameters(array $endpointParameters)
    {
        $this->endpointParameters = $endpointParameters;

        return $this;
    }

    /**
     * @return string
     */
    protected function buildUrlPath()
    {
        return (!empty(static::$endpointPath) ? static::$endpointPath.'/' : '').implode('/', $this->endpointParameters);
    }

    /**
     * Generalized retrieval of a sub endpoint that is similar for multiple models.
     *
     * @param null $id
     * @param      $subEndpoint
     * @param null $model
     *
     * @throws \GmodStore\API\Exceptions\EndpointException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return array|\GmodStore\API\Collection
     */
    protected function getGeneralSubEndpoint($id, $subEndpoint, $model = null)
    {
        if ($id) {
            $this->setId($id);
        }

        $this->endpointParameters[] = $subEndpoint;

        $response = $this->send();
        $response = $response->getBody()->getContents();

        $data = json_decode($response, true);
        $data = $data !== false ? $data['data'] : [];

        $collection = new Collection();

        if (empty($model)) {
            $collection->setAttributes($data);
        } elseif (!class_exists($model)) {
            throw new InvalidArgumentException('`'.$model.'` does not exist.');
        } else {
            foreach ($data as $row) {
                $collection[] = new $model($row);
            }
        }

        return $collection;
    }
}
