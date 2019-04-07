<?php

namespace GmodStore\API;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use function is_array;

class ClientFixed
{
    /**
     * @var string
     */
    protected $secret;

    /**
     * Client options
     *
     * @var array
     */
    protected $options = [];

    /**
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;

    /**
     * ClientFixed constructor.
     *
     * @param       $secret
     * @param array $options
     *
     * @throws \Exception
     */
    public function __construct($secret, array $options = [])
    {
        if (is_array($secret)) {
            $options = $secret;
            $secret = $options['secret'] ?? null;
            unset($options['secret']);
        }

        if (empty($secret)) {
            throw new Exception('`$secret` not given');
        }

        $this->setSecret($secret);
        $this->parseOptions($options);
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     *
     * @return \GmodStore\API\ClientFixed
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;

        return $this;
    }

    protected function parseOptions(array $options = [])
    {
        if (isset($options['guzzle']) && $options['guzzle'] instanceof GuzzleClient) {
            $this->guzzle = $options['guzzle'];
        }

        if (isset($options['guzzleOptions']) && empty($this->guzzle)) {
            $this->guzzle = new GuzzleClient($options['guzzleOptions']);
        }
    }
}
