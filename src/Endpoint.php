<?php

namespace GmodStore\API;

use GmodStore\API\Interfaces\EndpointInterface;
use function implode;
use function json_decode;

abstract class Endpoint implements EndpointInterface
{
    /**
     * @var string
     */
    protected static $endpointPath;

    /**
     * @var array
     */
    protected static $endpointParameters = [];

    /**
     * @var \GmodStore\API\Interfaces\ModelInterface
     */
    protected static $model;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var \GmodStore\API\Client
     */
    protected $client;

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

    public function setId($id)
    {
        $this->id = $id;
        static::$endpointParameters = [$id];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get($id = null)
    {
        $this->client->setRequestMethod('GET');

        if ($id) {
            $this->setId($id);
        }

        $response = $this->client->setEndpoint($this->buildUrlPath())->send();

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function buildUrlPath()
    {
        return static::$endpointPath.'/'.implode('/', static::$endpointParameters);
    }
}