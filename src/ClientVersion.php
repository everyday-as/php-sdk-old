<?php

namespace GmodStore\API;

use GmodStore\API\Interfaces\ClientVersionInterface;

abstract class ClientVersion implements ClientVersionInterface
{
    /**
     * @var \GmodStore\API\Client
     */
    protected $client;

    /**
     * @var \GmodStore\API\Model
     */
    protected $model;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return __CLASS__;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUrl(): string
    {
        return $this->client->buildEndpointUrl();
    }

    /**
     * @param string $model
     *
     * @return $this|mixed
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }
}
