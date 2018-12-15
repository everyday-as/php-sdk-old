<?php

namespace kanalumaddela\GmodStoreAPI;

use Exception;

class Addon extends Model
{
    /**
     * {@inheritdoc}
     */
    public static $endpoint = 'addons';

    /**
     * {@inheritdoc}
     */
    protected static $modelRelations = [
        'team' => Team::class,
    ];

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

    /**
     * Get all purchases for an addon.
     *
     * @param bool $withUser
     *
     * @throws Exception
     *
     * @return array|bool|mixed
     */
    public function getPurchases($withUser = false)
    {
        if (!in_array('user', $this->withRelations) && $withUser === true) {
            $this->withRelations[] = 'user';
            $this->newRequest()->with($this->withRelations);
        }

        if (\is_null($purchases = $this->newRequest()->getPurchases())) {
            $purchases = [];
        }

        if (($length = \count($purchases)) > 0) {
            for ($i = 0; $i < $length; $i++) {
                $purchase = $purchases[$i];

                if (isset($purchase->user)) {
                    $purchase->user = (new User($purchase->user))->setClient($this->getClient())->with($this->withRelations)->forceExists()->fixRelations();
                }

                //$addon = clone $this;
                $purchase->addon_id = $this->id;

                $purchases[$i] = $purchase;
            }
        }

        $this->setRelation('purchases', $purchases);

        return $purchases;
    }

    /**
     * Get all coupons for an addon.
     *
     * @throws Exception
     *
     * @return array|mixed|null
     */
    public function getCoupons()
    {
        if (\is_null($coupons = $this->newRequest()->getCoupons())) {
            $coupons = [];
        }

        dump($this->client->getWith());
        die();

        if (($length = \count($coupons)) > 0) {
            for ($i = 0; $i < $length; $i++) {
                $coupon = (new Coupon($coupons[$i]))->setClient($this->getClient())->with($this->withRelations)->forceExists()->fixRelations();

                //$addon = clone $this;
                $coupons[$i] = $coupon->setRelation('addon_id', $this->id);
            }
        }

        $this->setRelation('coupons', $coupons);

        return $coupons;
    }

    /**
     * Get all versions for an addon.
     *
     * @throws Exception
     *
     * @return array|bool|mixed
     */
    public function getVersions()
    {
        if (\is_null($versions = $this->newRequest()->getVersions())) {
            $versions = [];
        }

        $this->setRelation('versions', $versions);

        if (!$this->relationLoaded('latest_version')) {
            $this->setRelation('latest_version', $versions[0]);
            $this->withRelations = array_merge(['latest_version'], $this->withRelations);
        }

        return $versions;
    }
}
