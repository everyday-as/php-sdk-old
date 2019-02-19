<?php

namespace GmodStore\API;

use BadMethodCallException;
use Exception;
use GmodStore\API\Versions\V2;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use ReflectionMethod;

class Client
{
    const VERSION = '0.0.1';

    /**
     * Current version being used by the client.
     *
     * @var ClientVersion
     */
    protected $clientVersion;

    /**
     * List of deprecated API versions to trigger a warning on.
     *
     * @var array
     */
    protected static $deprecatedApis = [
        //V1::class,
    ];

    /**
     * Current API version to use by default.
     *
     * @var string
     */
    protected static $latestVersion = V2::class;

    /**
     * API key for gmodstore.
     *
     * @var string
     */
    protected $secret;

    /**
     * API endpoint name.
     *
     * @var string
     */
    protected $endpoint = '';

    /**
     * API endpoint url path.
     *
     * @var string
     */
    protected $endpointUrl = '';

    /**
     * Array of params for endpoint.
     *
     * @var array
     */
    protected $endpointParams = [];

    /**
     * Data to send to the endpoint for POST/PUT/PATCH requests.
     *
     * @var array
     */
    protected $endpointData = [];

    /**
     * @var array
     */
    public static $modelRelations = [
        'addon'          => Models\Addon::class,
        'latest_version' => Models\AddonVersion::class,
        'team'           => Models\Team::class,
        'author'         => Models\User::class,
        'user'           => Models\User::class,
        'primaryAuthor'  => Models\PrimaryAuthor::class,
        'primary_author' => Models\PrimaryAuthor::class,
    ];

    /**
     * The full API request URL.
     *
     * @var string
     */
    protected $requestUrl = '';

    /**
     * Array of relationships.
     *
     * @var array
     */
    protected $with = [];

    /**
     * HTTP method for request.
     *
     * @var string
     */
    protected $method = 'GET';

    /**
     * Guzzle instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;

    /**
     * Guzzle options.
     *
     * @var array
     */
    protected $guzzleOptions = [];

    /**
     * Guzzle response.
     *
     * @var \GuzzleHttp\Psr7\Response
     */
    protected $response;

    /**
     * Response body content.
     *
     * @var
     */
    protected $responseBody;

    /**
     * Error response, if any.
     *
     * @var
     */
    protected $error;

    /**
     * Client constructor.
     *
     * @param       $secret
     * @param array $options
     *
     * @throws Exception
     */
    public function __construct($secret, array $options = [])
    {
        if (!\is_string($secret) && !\is_array($secret)) {
            throw new InvalidArgumentException('$secret must be a string or an array of options with a [\'secret\'] key');
        }

        if (\is_array($secret)) {
            $options = $secret;
            $secret = $options['secret'];
        }
        if (isset($options['guzzle']) && !$options['guzzle'] instanceof GuzzleClient) {
            throw new InvalidArgumentException('$options[\'guzzle\'] must be an instance of \GuzzleHttp\Client');
        }

        $this->setSecret($secret)
            ->setClientVersion($options['version'] ?? self::$latestVersion)
            ->setGuzzleOptions($options['guzzleOptions'] ?? [])
            ->setGuzzle($options['guzzle'] ?? new GuzzleClient($this->guzzleOptions));
    }

    /**
     * Magic __call method to pass methods to the current version client.
     *
     * @param $name
     * @param $arguments
     *
     * @throws \ReflectionException
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (!method_exists($this->clientVersion, $name)) {
            throw new BadMethodCallException();
        }

        if (strpos($name, 'get') === 0) {
            $reflectionMethod = new ReflectionMethod($this->clientVersion, $name);

            if ($reflectionMethod->getNumberOfRequiredParameters() < ($argLength = count($arguments))) {
                $this->with($arguments[$argLength - 1]);
            }
        }

        $return = call_user_func_array([$this->clientVersion, $name], $arguments);

        return !$return instanceof ClientVersion ? $return : $this;
    }

    /**
     * @return GuzzleClient
     */
    public function getGuzzle(): GuzzleClient
    {
        return $this->guzzle;
    }

    /**
     * @param GuzzleClient $guzzle
     */
    public function setGuzzle(GuzzleClient $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * @param string $secret
     *
     * @return $this
     */
    public function setSecret($secret): self
    {
        $this->secret = $secret;

        return $this->setHeaders();
    }

    /**
     * @return \GmodStore\API\ClientVersion
     */
    public function getClientVersion(): ClientVersion
    {
        return $this->clientVersion;
    }

    /**
     * Set the API client version to use.
     *
     * @param $version
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setClientVersion($version): self
    {
        if (!class_exists($version)) {
            throw new Exception("{$version} could not be found");
        }

        $this->clientVersion = new $version($this);

        return $this;
    }

    public function setMethod($method)
    {
        if (!in_array($method, ['get', 'post', 'put', 'delete', 'patch', 'options', 'head'])) {
            throw new InvalidArgumentException("{$method} is not a valid HTTP method.");
        }

        $this->method = $method;

        return $this;
    }

    /**
     * Configure options for Guzzle client.
     *
     * @param array $options
     *
     * @return self
     */
    public function setGuzzleOptions(array $options): self
    {
        $this->guzzleOptions = $options;

        $this->setHeaders($options['headers'] ?? []);

        return $this;
    }

    public function setGuzzleOption($name, $data)
    {
        if ($name === 'headers') {
            $this->setHeaders($data);
        } else {
            $this->guzzleOptions[$name] = $data;
        }

        return $this;
    }

    /**
     * Set Guzzle headers.
     *
     * @param array $headers
     *
     * @return self
     */
    public function setHeaders(array $headers = []): self
    {
        if (count($headers) === 0 && isset($this->guzzleOptions['headers'])) {
            $headers = $this->guzzleOptions['headers'];
        }

        $this->guzzleOptions['headers'] = \array_merge($headers, ['Authorization' => "Bearer {$this->secret}", 'User-Agent' => 'gmodstore/gmodstore-php-sdk v'.self::VERSION]);

        return $this;
    }

    /**
     * Make the request and return the response instance.
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function send(): Response
    {
        $this->response = $this->guzzle->{$this->method}($this->buildEndpointUrl(), $this->guzzleOptions);

        return $this->response;
    }

    /**
     * @param $data
     *
     * @return Collection
     */
    public function parseData(array $data = []): Collection
    {
        if (empty($data)) {
            return new Collection();
        }

        foreach ($data as $key => $value) {
            $row = $data[$key];

            if (\is_array($row)) {
                if (!empty($row)) {
                    foreach ($row as $subKey => $subRow) {
                        if (\is_array($subRow)) {
                            $row[$subKey] = $this->parseData($subRow);
                        }
                    }
                }

                $row = new Collection($row);

                if (isset(self::$modelRelations[$key])) {
                    $row = (new self::$modelRelations[$key]($row, $this))->forceExists()->fixRelations();
                }

                $data[$key] = $row;
            }
        }

        return new Collection($data);
    }

    /**
     * Relations to load.
     *
     * @param mixed ...$relations
     *
     * @return $this
     */
    public function with(...$relations): self
    {
        if (isset($relations[0]) && \is_array($relations[0])) {
            $relations = $relations[0];
        }

        $this->with = array_unique(array_values(array_filter($relations)));

        return $this;
    }

    /**
     * Get the array of ?with relations.
     *
     * @return array
     */
    public function getWith(): array
    {
        return $this->with;
    }

    /**
     * Get the current endpoint set on the client.
     *
     * @throws Exception
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        if (empty($this->endpoint)) {
            throw new Exception('API endpoint not set.');
        }

        return $this->endpoint;
    }

    /**
     * Set the current endpoint name being used.
     * Can be in dot notation e.g. addons.coupons.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setEndpoint($name): self
    {
        $this->endpoint = $name;

        return $this;
    }

    /**
     * Set the endpoint URL.
     *
     * @param string $url
     *
     * @return Client
     */
    public function setEndpointUrl($url): self
    {
        $this->endpointUrl = $url;

        return $this;
    }

    public function setEndpointParam($name, $param): self
    {
        $this->endpointParams[':'.$name] = $param;

        return $this;
    }

    public function appendEndpointUrl($append): self
    {
        $this->endpointUrl = $this->endpointUrl.$append;

        return $this;
    }

    /**
     * Add parameters to the endpoint.
     *
     * @param array $params
     *
     * @return Client
     */
    public function addEndpointParams(array $params): self
    {
        array_push($this->endpointParams, ...$params);

        return $this;
    }

    /**
     * Get the endpoint URL with the parameters added in.
     *
     * @return string
     */
    public function buildEndpointUrl(): string
    {
        return str_replace(array_keys($this->endpointParams), array_values($this->endpointParams), $this->endpointUrl);
    }
}
