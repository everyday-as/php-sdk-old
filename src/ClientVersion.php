<?php

namespace GmodStore\API;

use GmodStore\API\Interfaces\ClientVersionInterface;

abstract class ClientVersion implements ClientVersionInterface
{
    /**
     * @var \GmodStore\API\Client
     */
    protected $client;

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
}
