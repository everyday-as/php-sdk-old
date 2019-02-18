<?php

namespace GmodStore\API\Models;

use GmodStore\API\Client;
use GmodStore\API\Model;

class Addon extends Model
{
    public static $endpoint = 'addon';

    protected static $validRelations = [
        'coupons',
        'purchases',
        'reviews',
        'versions',
    ];

    protected static $validWithRelations = [
        'latest_version',
        'team',
    ];

    /**
     * {@inheritdoc}
     */
    protected static $modelRelations = [
        'latest_version' => AddonVersion::class,
        'team'           => Team::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct($attributes = [], Client $client = null)
    {
        parent::__construct($attributes, $client);

        if (isset($this->attributes['price'])) {
            $this->attributes['price']['original']['amount'] = $this->attributes['price']['original']['amount'] / 100;
            $this->attributes['price']['purchase']['amount'] = $this->attributes['price']['purchase']['amount'] / 100;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fresh()
    {
        parent::fresh();

        if (isset($this->attributes['price'])) {
            $this->attributes['price']['original']['amount'] = $this->attributes['price']['original']['amount'] / 100;
            $this->attributes['price']['purchase']['amount'] = $this->attributes['price']['purchase']['amount'] / 100;
        }

        return $this;
    }

    public function getPurchases($withUser = false, $withTransaction = false)
    {
        $with = [
            $withUser ? 'user' : '',
            $withTransaction ? 'transaction' : '',
        ];

        \call_user_func_array([$this->client, 'with'], $with);

        return $this->__call(__FUNCTION__, []);
    }

    /**
     * Get all versions for an addon.
     *
     * @return array|mixed
     */
    public function getVersions()
    {
        $this->__call(__FUNCTION__, []);

        $versions = $this->getRelation('versions');

        if ($versions && !$this->relationLoaded('latest_version')) {
            $this->setRelation('latest_version', $versions[0]);
            $this->withRelations = array_merge(['latest_version'], $this->withRelations);
        }

        return $versions;
    }
}
