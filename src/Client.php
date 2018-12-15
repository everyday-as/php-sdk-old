<?php

namespace kanalumaddela\GmodStoreAPI;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use InvalidArgumentException;
use ReflectionClass;
use SteamID;

class Client
{
    /**
     * gmodstore v1 api url.
     *
     * @var string
     */
    const GMS_V1_API = 'https://api.gmodstore.com/';

    /**
     * gmodstore v2 api url.
     *
     * @var string
     */
    const GMS_V2_API = 'https://api.gmodstore.com/v2/';

    /**
     * List of deprecated API versions to trigger a warning on.
     *
     * @var array
     */
    protected static $deprecatedApis = [
        'v1',
    ];

    /**
     * Current API version to use by default.
     *
     * @var string
     */
    protected static $latestApi = 'v2';

    /**
     * current api version being used.
     *
     * @var string
     */
    protected $version;

    /**
     * gmodstore api version url currently being used.
     *
     * @var string
     */
    protected $apiVersionUrl;

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

    protected static $endpoints = [
        'v1' => [
            'addons' => 'api/scripts/info',
            'users'  => 'users/search/steam64',
        ],
        'v2' => [
            'addons' => 'addons',
            'teams'  => 'teams',
            'users'  => 'users',
        ],
    ];

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
     * Array of urls paths to append to endpoint.
     *
     * @var array
     */
    protected $endpointExtras = [];

    /**
     * The full API request URL.
     *
     * @var string
     */
    protected $requestUrl = '';

    /**
     * @var array
     */
    protected static $modelRelations = [
        'addon'  => Addon::class,
        'coupon' => Coupon::class,
        'user'   => User::class,
        'team'   => Team::class,
    ];

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
     * @param $secret
     * @param array $options
     *
     * @throws Exception
     */
    public function __construct($secret, array $options = [])
    {
        if (!\is_string($secret)) {
            throw new InvalidArgumentException('$secret must be a string.');
        }

        $this->secret = $secret;

        $this->setApiVersion(\substr(self::$latestApi, 1));

        if (isset($options['version'])) {
            $this->__call($options['version'], []);
        }

        $this->guzzle = new GuzzleClient();

        if (!isset($options['guzzle'])) {
            $options['guzzle'] = [];
        }

        $this->guzzleOptions = \array_merge($options['guzzle'], ['headers' => ['Authorization' => "Bearer {$this->secret}"]]);
    }

    /**
     * Dumb shit to dynamically set API version
     * cause I'm too lazy to manually define v1(), v2(), etc.
     *
     * @param $name
     * @param $arguments
     *
     * @throws Exception
     *
     * @return Client|null
     */
    public function __call($name, $arguments)
    {
        if (\preg_match('/(v\d+)/', $name) === 1) {
            return $this->setApiVersion(\substr($name, 1), (isset($arguments[0]) ? $arguments[0] : false));
        }
    }

    /**
     * Get currently set API version.
     *
     * @return string
     */
    public function getCurrentApiVersion()
    {
        return $this->apiVersionUrl;
    }

    /**
     * Set API version.
     *
     * @param $version
     * @param bool $ignoreDeprecated
     *
     * @throws Exception
     *
     * @return $this
     */
    protected function setApiVersion($version, $ignoreDeprecated = false)
    {
        $version = (int) $version;
        $versionName = 'v'.$version;

        if (!\is_bool($ignoreDeprecated)) {
            $ignoreDeprecated = false;
        }

        $constant = 'self::GMS_V'.$version.'_API';
        if (!\defined($constant)) {
            throw new Exception('This API version is not defined.');
        }

        if (\in_array($versionName, self::$deprecatedApis)) {
            if (!$ignoreDeprecated) {
                \trigger_error("V{$version} API is deprecated and can lead to unexpected results. If you fully understand this, pass the \$ignoreDeprecated = true arg in this method or pass \$options['ignoreDeprecatedApi'] = true");
            }
        }

        $this->apiVersionUrl = constant($constant);
        $this->version = $versionName;

        return $this;
    }

    /**
     * Set Guzzle headers.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->guzzleOptions['headers'] = \array_merge($headers, ['Authorization' => "Bearer {$this->secret}"]);
    }

    /**
     * Build the request URL.
     *
     * @return string
     */
    public function buildRequestUrl()
    {
        return \vsprintf($this->apiVersionUrl.$this->endpointUrl.\implode('/', $this->endpointExtras), $this->endpointParams);
    }

    /**
     * Return the return URL.
     *
     * @return string
     */
    public function getRequestUrl()
    {
        $this->requestUrl = $this->buildRequestUrl();

        return $this->requestUrl;
    }

    /**
     * Perform the request and return the appropriate data.
     *
     * @param bool $assoc
     *
     * @throws Exception
     * @throws GuzzleException
     *
     * @return bool|mixed
     */
    public function send($assoc = false)
    {
        $this->error = null;
        $data = null;

        /*
        $this->requestUrl = $this->apiVersionUrl.$this->endpointUrl.\implode('/', $this->endpointExtras);
        $this->requestUrl = \vsprintf($this->requestUrl, $this->endpointParams);
        */

        if (isset($this->guzzleOptions['query']) && $this->method !== 'get') {
            unset($this->guzzleOptions['query']);
        }

        if ($this->apiVersionUrl === self::GMS_V2_API && $this->method === 'get') {
            if (count($with = $this->getWith()) > 0) {
                $this->guzzleOptions['query'] = ['with' => \implode(',', $this->getWith())];
            }
        }

        if (in_array($this->method, ['put', 'post', 'patch'])) {
            $this->guzzleOptions['body'] = $this->endpointData;
        }

        try {
            $this->response = $this->guzzle->request($this->method, $this->getRequestUrl(), $this->guzzleOptions);
            $this->responseBody = $this->response->getBody()->getContents();

            $data = \json_decode($this->responseBody, $assoc);

            switch ($this->apiVersionUrl) {
                case self::GMS_V1_API:
                    switch ($this->endpoint) {
                        case 'users':
                            $data = $assoc ? (count($data['user']) > 0 ? $data['user'] : null) : (count($data->user) > 0 ? $data->user : null);
                            break;
                        case 'addons':
                            break;
                    }
                    break;
                case self::GMS_V2_API:
                    if (!empty($data)) {
                        $data = $assoc ? $data['data'] : $data->data;
                    }
                    break;
            }
        } catch (Exception $e) {
            if ($e instanceof ServerException) {
                throw $e;
            }
            var_dump($e);
            die();
        }

        switch ($this->method) {
            case 'get':
                return $data;
                break;
            case 'post':
                var_dump($data); die();

                return $this->response->getStatusCode() === 201;
                break;
            case 'put':
                var_dump($data); die();

                return $this->response->getStatusCode() === 200;
                break;
            case 'delete':
                var_dump('delete');
                var_dump($this->response->getStatusCode());
                die();

                return $this->response->getStatusCode() === 204;
                break;
        }
    }

    /**
     * Get the 'raw' response data.
     *
     * @param bool $assoc
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function raw($assoc = false)
    {
        return $this->send($assoc);
    }

    /**
     * @throws Exception
     *
     * @return bool|Collection|mixed
     */
    public function get()
    {
        if (empty($this->endpoint)) {
            throw new Exception('API endpoint not set.');
        }

        /*
        if (is_bool($id)) {
            $assoc = $id;
            $id = null;
        }
        if (is_int($id)) {
            $this->set($id);
        }
        */

        $data = $this->raw();

        switch ($this->endpoint) {
            case 'addons':
                if (array_intersect(['coupons', 'purchases', 'versions'], $this->endpointExtras)) {
                }
                break;
            case 'teams':
                break;
            case 'users':
                if (array_intersect(['purchases', 'teams'], $this->endpointExtras)) {
                    $data = $this->parseData($data);
                }
                break;
        }

        return $data;
    }

    /**
     * @param $data
     *
     * @return Collection
     */
    protected function parseData($data)
    {
        if (($length = \count($data)) < 1) {
            return $data;
        }

        $keys = \is_array($data) ? \array_keys($data) : \array_keys(\get_object_vars($data));

        if (\is_int($keys[0])) {
            for ($i = 0; $i < $length; $i++) {
                $row = $data[$i];
                $rowKeys = \array_keys(\get_object_vars($row));

                if (($lengthRow = \count($rowKeys)) > 0) {
                    for ($x = 0; $x < $lengthRow; $x++) {
                        $key = $rowKeys[$x];

                        if (is_object($row->{$key})) {
                            $row->{$key} = isset(self::$modelRelations[$key]) ? new self::$modelRelations[$key](new Collection($row->{$key})) : $this->parseData($row->{$key});
                        }
                    }
                }

                $data[$i] = new Collection($data[$i]);
            }
        } else {
            $length = count($keys);

            for ($z = 0; $z < $length; $z++) {
                $key = $keys[$z];

                if (is_object($data->{$key})) {
                    $data->{$key} = isset(self::$modelRelations[$key]) ? new self::$modelRelations[$key](new Collection($data->{$key})) : $this->parseData($data->{$key});
                }
            }
        }

        return new Collection($data);
    }

    public function delete()
    {
        $this->method = 'delete';

        return $this->send();
    }

    /**
     * @param mixed ...$params
     *
     * @throws Exception
     *
     * @return $this
     */
    public function set(...$params)
    {
        if (empty($this->endpoint)) {
            throw new Exception('API endpoint not set.');
        }

        $this->setEndpointParams($params);

        return $this;
    }

    /**
     * Return the response.
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**'
     * Return the body content of the response
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->responseBody;
    }

    /**
     * Check if request failed.
     *
     * @return bool
     */
    public function failed()
    {
        return !\is_null($this->error);
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /*** START V1 API METHODS ***/

    /**
     * Search users by name or steamid.
     *
     * @param $search
     *
     * @return $this
     */
    public function searchUsers($search)
    {
        $this->checkVersion(self::GMS_V1_API);

        $this->method = 'get';
        $this->endpointUrl = 'users/search/:user';

        // todo: fix this

        return $this;
    }

    /*** END V1 API METHODS ***/

    /*** START V2 API METHODS ***/

    /**
     * Relations to load.
     *
     * @param mixed ...$relations
     *
     * @return $this
     */
    public function with(...$relations)
    {
        if (isset($relations[0]) && \is_array($relations[0])) {
            $relations = $relations[0];
        }

        $this->with = $relations;

        return $this;
    }

    /**
     * Get the details of the user.
     *
     * @param $user
     *
     * @return User
     */
    public function getUser($user)
    {
        return (new User($user))->setClient($this);
    }

    /**
     * Set a specific addon to retrieve.
     *
     * @param $id
     */
    public function addon($id)
    {
        $this->setEndpoint();
    }

    /**
     * Use the addons endpoint.
     *
     * @param null $id
     *
     * @return $this
     */
    public function addons($id = null)
    {
        $this->method = 'get';
        $this->endpoint = 'addons';
        $this->clearEndpointExtras();

        switch ($this->apiVersionUrl) {
            case self::GMS_V1_API:
                $this->endpointUrl = 'scripts/info/%s';
                break;
            case self::GMS_V2_API:
                $this->endpointUrl = 'addons/';
                break;
        }

        if (is_int($id)) {
            $this->endpointExtras = ['%s'];
            $this->addEndpointParams($id);
        }

        return $this;
    }

    /**
     * Sets the current api endpoint as teams.
     *
     * @return $this
     */
    public function teams($teamId = null)
    {
        $this->method = 'get';

        if ($this->endpoint === 'users' && is_null($teamId)) {
            $this->endpointExtras = ['teams'];
        } else {
            $this->endpoint = 'teams';
            $this->endpointUrl = 'teams/%s/';
            $this->clearEndpointExtras();

            if (!\is_null($teamId) && (\is_int($teamId) || \is_string($teamId))) {
                $this->set($teamId);
            }
        }

        return $this;
    }

    /**
     * Retrieve multiple users.
     *
     * @return $this
     */
    public function users(...$users)
    {
        if (($length = count($users)) === 0) {
            throw new InvalidArgumentException('Array or list of users not given');
        }

        $data = [];

        for ($i = 0; $i < $length; $i++) {
            $data[] = $this->user($users[$i])->get();
        }

        return $data;
    }

    /**
     * Get a single user.
     *
     * @param $user
     *
     * @throws Exception
     *
     * @return $this
     */
    public function user($user)
    {
        $this->method = 'get';
        $this->endpoint = 'users';
        $this->clearEndpointExtras();

        switch ($this->apiVersionUrl) {
            case self::GMS_V1_API:
                $this->endpointUrl = 'users/search/steam64/%s';
                break;
            case self::GMS_V2_API:
                $this->endpointUrl = 'users/%s/';
                break;
        }

        if (!\is_null($user) && (\is_int($user) || \is_string($user))) {
            $this->set($user);
        }

        return $this;
    }

    /**
     * Add the coupons sub-endpoint.
     *
     * @param null $id
     *
     * @throws Exception
     *
     * @return $this
     */
    public function coupons($couponId = null)
    {
        if ($this->endpoint !== 'addons') {
            throw new Exception('API endpoint must be \'addons\'');
        }

        $this->endpointExtras[] = 'coupons';

        if (is_int($couponId)) {
            $this->endpointExtras[] = '%s';
            $this->addEndpointParams($couponId);
        }

        return $this;
    }

    public function purchases()
    {
        if (!in_array($this->endpoint, ['addons', 'users'])) {
            throw new Exception("API endpoint must be 'addons' or 'users'");
        }

        $this->endpointExtras[] = 'purchases';

        return $this;
    }

    /**
     * Add the coupons sub-endpoint.
     *
     * @param null $versionId
     *
     * @throws Exception
     *
     * @return $this
     */
    public function versions($versionId = null)
    {
        if ($this->endpoint !== 'addons') {
            throw new Exception('API endpoint must be \'addons\'');
        }

        $this->endpointExtras[] = 'versions';

        if (is_int($versionId)) {
            $this->endpointExtras[] = '%s';
            $this->addEndpointParams($versionId);
        }

        return $this;
    }

    /**
     * Get the details of a team.
     *
     * @param $id
     *
     * @return Team
     */
    public function getTeam($id)
    {
        return (new Team($id))->setClient($this);
    }

    /**
     * Get the addons of the user of the api key given.
     *
     * @throws Exception
     *
     * @return array|mixed|null
     */
    public function getMyAddons()
    {
        if (\is_null($addons = $this->addons()->get())) {
            $addons = [];
        }

        if (($length = count($addons)) > 0) {
            for ($i = 0; $i < $length; $i++) {
                $addons[$i] = (new Addon($addons[$i]))->setClient($this)->with($this->getWith())->forceExists()->fixRelations();
            }
        }

        return $addons;
    }

    /**
     * @param $id
     *
     * @return Addon
     */
    public function getAddon($id)
    {
        return (new Addon($id))->setClient($this);
    }

    /**
     * @param null $addonId
     *
     * @throws Exception
     *
     * @return mixed|null
     */
    public function getCoupons($addonId = null)
    {
        $this->method = 'get';

        if (is_int($addonId)) {
            $this->addons($addonId);
        }

        if (empty($this->endpoint) || $this->endpoint !== 'addons') {
            throw new Exception(empty($this->endpoint) ? 'API endpoint not set. Make sure you pass the $addonId' : "API endpoint must be 'addons'. Current endpoint: {$this->endpoint}");
        }

        $this->coupons();

        return $this->send();
    }

    /**
     * Get purchases for a specified endpoint.
     * If no endpoint is specified, default to users().
     *
     * @param null $id
     * @param bool $withAddon
     * @param bool $assoc
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function getPurchases($id = null, $withAddon = false, $assoc = false)
    {
        if (\is_bool($id)) {
            if (empty($this->endpoint)) {
                throw new Exception('API endpoint not set, but 1st arg is a boolean.');
            }
            if (\is_bool($withAddon)) {
                $assoc = $withAddon;
            }
            $withAddon = $id;
            $id = null;
        }

        if ($withAddon) {
            $this->with[] = 'addon';
        }

        if (!\is_null($id)) {
            if (empty($this->endpoint)) {
                $this->users($id);
            } else {
                $this->set($id);
            }
        }

        $this->endpointExtras = ['purchases'];

        return $this->send($assoc);
    }

    public function getVersions($addonId = null)
    {
        $this->method = 'get';

        if (is_int($addonId)) {
            $this->addons($addonId);
        }

        if (empty($this->endpoint) || $this->endpoint !== 'addons') {
            throw new Exception(empty($this->endpoint) ? 'API endpoint not set. Make sure you pass the $addonId' : "API endpoint must be 'addons'. Current endpoint: {$this->endpoint}");
        }

        $this->apiVersionUrls();

        return $this->send();
    }

    /**
     * Get the users for a team.
     *
     * @param null $id
     *
     * @return $this
     */
    public function getUsers($id = null, $assoc = false)
    {
        if (empty($this->endpoint)) {
            throw new Exception('API endpoint not set');
        }

        if (\is_bool($id)) {
            $assoc = $id;
            $id = null;
        }

        if (!\is_null($id)) {
            $this->setEndpointParams([$id]);
        }

        $this->with('user');
        $this->endpointExtras = ['users'];

        return $this->send($assoc);
    }

    public function getTeams($id = null, $assoc = false)
    {
        if (empty($this->endpoint)) {
            throw new Exception('User API endpoint not set');
        }

        if (\is_bool($id)) {
            $assoc = $id;
            $id = null;
        }

        if (!\is_null($id)) {
            $this->setEndpointParams([$id]);
        }

        $this->endpointExtras = ['teams'];

        return $this->send($assoc);
    }

    /*** END V2 API METHODS ***/

    public function getWith()
    {
        $this->fixWidth();

        return $this->with;
    }

    protected function setEndpoint($endpoint)
    {
        if (!isset(self::$endpoints[$this->version][$endpoint])) {
            throw new Exception('A path for this endpoint does not exist');
        }

        $this->endpoint = self::$endpoints[$this->version][$endpoint];

        return $this;
    }

    protected function fixWidth()
    {
        $this->with = array_unique($this->with);

        return $this;
    }

    protected function clearEndpointExtras()
    {
        $this->endpointExtras = [];

        return $this;
    }

    protected function setEndpointParams(array $params)
    {
        $this->endpointParams = $params;

        return $this;
    }

    protected function addEndpointParams(...$params)
    {
        $this->endpointParams = \array_merge($this->endpointParams, $params);

        return $this;
    }

    protected function checkVersion($expected = self::GMS_V2_API)
    {
        if ($this->apiVersionUrl !== $expected) {
            try {
                $reflection = new ReflectionClass(self::class);
                $reflectionConstants = \array_flip($reflection->getConstants());
                $api = $reflectionConstants[$this->apiVersionUrl];
            } catch (ReflectionException $e) {
                $api = 'this API version';
            }

            throw new InvalidArgumentException('This method is not available in '.$api);
        }
    }
}
