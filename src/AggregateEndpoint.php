<?php

namespace GmodStore\API;

use GmodStore\API\Exceptions\EndpointException;
use GmodStore\API\Interfaces\EndpointInterface;
use InvalidArgumentException;
use function call_user_func_array;
use function method_exists;

class AggregateEndpoint implements EndpointInterface
{
    /**
     * @var \GmodStore\API\Endpoint
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $subEndpoint;

    /**
     * @var array
     */
    protected $subEndpointArgs = [];

    /**
     * @var \GmodStore\API\Model
     */
    protected $subEndpointModel;

    /**
     * @var \GmodStore\API\Model
     */
    protected $model;

    /**
     * @var array
     */
    protected $ids;

    /**
     * @var array
     */
    protected $clientWith = [];

    public function __construct(Endpoint $endpoint, array $ids = [])
    {
        $this->endpoint = $endpoint;
        $this->model = $endpoint::$model;
        $this->ids = $ids;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @throws \GmodStore\API\Exceptions\EndpointException
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->endpoint, $name)) {
            call_user_func_array([$this->endpoint, $name], $arguments);

            return $this;
        }

        if ($this->endpoint::hasEndpoint($name)) {
            $this->subEndpoint = $name;
            $this->subEndpointArgs = $arguments;
            $this->subEndpointModel = $this->endpoint::getEndpointModel($name);

            return $this;
        }

        throw new EndpointException('`'.$name.'` is not a valid method.');
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
    public function get(...$id)
    {
        if (empty($id) && empty($this->ids)) {
            throw new InvalidArgumentException('No ids given.');
        }

        $collection = new Collection();

        foreach ($this->ids as $id) {
            $this->endpoint->setId($id);

            if ($this->subEndpoint) {
                call_user_func_array([$this->endpoint, $this->subEndpoint], $this->subEndpointArgs);
            }
            if (!empty($this->clientWith)) {
                call_user_func_array([$this->endpoint, 'with'], $this->clientWith);
            }

            $data = $this->endpoint->get();

            if (!$data instanceof Model) {
                $collection[$id] = $data;
            } else {
                $collection[] = $data;
            }
        }

        // reset
        $this->subEndpoint = $this->subEndpointModel = null;
        $this->clientWith = $this->subEndpointArgs = [];

        return $collection;
    }
}
