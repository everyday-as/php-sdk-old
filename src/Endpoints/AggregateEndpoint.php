<?php

namespace GmodStore\API\Endpoints;

use GmodStore\API\Client;
use GmodStore\API\Collection;
use GmodStore\API\Endpoint;
use GmodStore\API\Interfaces\EndpointInterface;

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
        $this->ids = $ids;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id = null)
    {
        parent::get($id);
        $id = null;

        $collection = new Collection();

        foreach ($this->ids as $id) {
            $collection[] = $this->endpoint->get($id);
        }

        return $collection;
    }
}
