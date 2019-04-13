<?php

namespace GmodStore\API;

use GmodStore\API\Exceptions\EndpointException;
use GmodStore\API\Interfaces\EndpointInterface;
use GuzzleHttp\Exception\ClientException;
use InvalidArgumentException;
use function array_diff;
use function count;
use function implode;
use function json_decode;
use function json_encode;

abstract class Endpoint implements EndpointInterface
{
    /**
     * @var string
     */
    protected static $endpointPath;

    /**
     * @var \GmodStore\API\Interfaces\ModelInterface
     */
    protected static $model;

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
    }

    public function __call($name, $arguments)
    {
        throw new EndpointException('`'.$name.'` is not a valid method.');
    }

    public function setId($id)
    {
        $this->id = $id;
        $this->endpointParameters = [$id];

        return $this;
    }

    public function with(...$with)
    {
        $model = static::$model;

        if (count($diff = array_diff($with, $model::$validWithRelations)) !== 0) {
            throw new InvalidArgumentException('Invalid $with given for '.$model.': '.json_encode($diff));
        }

        $this->clientWith = $with;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id = null)
    {
        $this->client->setRequestMethod('GET');

        if ($id) {
            $this->setId($id);
        }

        $response = $this->send();

        return json_decode($response, true);
    }

    protected function send()
    {
        try {
            $response = $this->client->setEndpoint($this->buildUrlPath())->setWith($this->clientWith)->send();

            $response = $response->getBody()->getContents();
        } catch (ClientException $e) {
            $response = null;

            throw new EndpointException('Request failed: '.$e->getMessage());
        }

        return $response;
    }

    protected function buildUrlPath()
    {
        return static::$endpointPath.'/'.implode('/', $this->endpointParameters);
    }
}
