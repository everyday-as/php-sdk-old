<?php

namespace GmodStore\API\Endpoints;

use GmodStore\API\Client;
use GmodStore\API\Collection;
use GmodStore\API\Endpoint;
use GmodStore\API\Interfaces\EndpointInterface;
use InvalidArgumentException;
use function array_merge;
use function count;
use function get_class;
use function is_array;
use function is_null;
use function json_decode;
use function property_exists;
use function ucfirst;

class AggregateEndpoint extends Endpoint
{
    /**
     * @var \GmodStore\API\Interfaces\EndpointInterface
     */
    protected $endpoint;

    /**
     * @var array
     */
    protected $ids;

    public function __construct(Client $client, EndpointInterface $endpoint, array $ids = [])
    {
        parent::__construct($client);

        $this->endpoint = $endpoint;
        self::$model = $endpoint::$model;
        $this->ids = $ids;
    }

    public function __call($name, $arguments)
    {
        if (property_exists($this->endpoint, 'endpoints') && get_class($this->endpoint)::$endpoints[$name]) {
            $this->endpointParameters[] = $name;

            return $this;
        }

        return parent::__call($name, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id = null)
    {
        if (!is_null($id) && !is_array($id)) {
            throw new InvalidArgumentException('Invalid arg given given. IDs must be passed as an array or set earlier');
        }

        $collection = new Collection();

        $this->endpoint->with(...$this->clientWith);

        foreach ($this->ids as $id) {
            if (count($this->endpointParameters) === 1) {
                $oldParams = $this->endpointParameters;
                $this->endpointParameters = array_merge([get_class($this->endpoint)::$endpointPath], [$id], $oldParams);

                $response = $this->send();
                $response = $response->getBody()->getContents();

                $data = json_decode($response, true);
                $data = $data !== false ? $data['data'] : [];

                $class = get_class($this->endpoint)::$endpoints[$oldParams[0]];

                $collection[$id] = new Collection();
                foreach ($data as $row) {
                    $collection[$id][] = new $class($row);
                }

                $this->endpointParameters = $oldParams;
            } else {
                $model = $this->endpoint->get($id);

                if (count($this->endpointParameters) === 1) {
                    $model->{'get'.ucfirst($this->endpointParameters[0])}();
                }

                $collection[] = $model;
            }
        }

        return $collection;
    }
}
