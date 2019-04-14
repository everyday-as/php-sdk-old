<?php

namespace GmodStore\API\Endpoints;

use GmodStore\API\Collection;
use GmodStore\API\Endpoint;
use GmodStore\API\Models\Addon;
use GmodStore\API\Models\AddonVersion;
use GmodStore\API\Models\Coupon;
use GmodStore\API\Models\Purchase;

class AddonEndpoint extends Endpoint
{
    public static $endpointPath = 'addons';

    public static $model = Addon::class;

    public static $endpoints = [
        'versions' => AddonVersion::class,
    ];

    public function get($id = null)
    {
        if ($id) {
            $data = parent::get($id);
        } else {
            $id = $this->id;
            $data = parent::get();
        }

        $data = $data['data'] ?? [];

        $model = new Collection();

        if (empty($this->id) && empty($id) && !empty($data)) {
            foreach ($data as $addon) {
                $model[] = new $this->currentModel($addon, $this);
            }
        }

        if (isset($data['id'])) {
            $model = new $this->currentModel($data, $this);
        } else {
            foreach ($data as $row) {
                $model[] = new $this->currentModel($row);
            }
        }

        return $model;
    }

    /**
     * Get an Addon's coupons.
     *
     * @param null $id
     *
     * @return array|\GmodStore\API\Collection
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @throws \GmodStore\API\Exceptions\EndpointException
     */
    public function getCoupons($id = null)
    {
        return $this->getGeneralSubEndpoint($id, 'coupons', Coupon::class);
    }

    /**
     * Get an Addon's purchases.
     *
     * @param null $id
     *
     * @return array|\GmodStore\API\Collection
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @throws \GmodStore\API\Exceptions\EndpointException
     */
    public function getPurchases($id = null)
    {
        return $this->getGeneralSubEndpoint($id, 'purchases', Purchase::class);
    }

    /**
     * Get an Addon's reviews.
     *
     * @param null $id
     *
     * @return array|\GmodStore\API\Collection
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @throws \GmodStore\API\Exceptions\EndpointException
     */
    public function getReviews($id = null)
    {
        return $this->getGeneralSubEndpoint($id, 'reviews');
    }

    /**
     * Get an Addon's versions.
     *
     * @param null $id
     *
     * @return array|\GmodStore\API\Collection
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @throws \GmodStore\API\Exceptions\EndpointException
     */
    public function getVersions($id = null)
    {
        return $this->getGeneralSubEndpoint($id, 'versions', AddonVersion::class);
    }
}
