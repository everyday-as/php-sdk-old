<?php

namespace GmodStore\API;

use GmodStore\API\Exceptions\EndpointException;
use GmodStore\API\Interfaces\EndpointInterface;
use GuzzleHttp\Exception\ClientException;
use InvalidArgumentException;
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

    /**
     * @param $name
     * @param $arguments
     *
     * @throws \GmodStore\API\Exceptions\EndpointException
     */
    public function __call($name, $arguments)
    {
        throw new EndpointException('`'.$name.'` is not a valid method.');
    }

    /**
     * @param $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        $this->endpointParameters = [$id];

        return $this;
    }

    public function with(...$with)
    {
//        $model = static::$model;
//
//        if (count($diff = array_diff($with, $model::$validWithRelations)) !== 0) {
//            throw new InvalidArgumentException('Invalid $with given for '.$model.': '.json_encode($diff));
//        }

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
        $response = $response->getBody()->getContents();

        return json_decode($response, true);
    }

    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @throws \GmodStore\API\Exceptions\EndpointException
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
     * @return string
     */
    protected function buildUrlPath()
    {
        return static::$endpointPath.'/'.implode('/', $this->endpointParameters);
    }

    /**
     * Generalized retrieval of a sub endpoint that is similar for multiple models.
     *
     * @param null $id
     * @param      $subEndpoint
     * @param null $model
     *
     * @return array|\GmodStore\API\Collection
     * @throws \GmodStore\API\Exceptions\EndpointException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getGeneralSubEndpoint($id = null, $subEndpoint, $model = null)
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
