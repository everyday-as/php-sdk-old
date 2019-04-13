<?php

namespace GmodStore\API\Models;

use GmodStore\API\Model;

class Addon extends Model
{
    /**
     * {@inheritdoc}
     */
    public static $validRelations = [
        'coupons',
        'purchases',
        'reviews',
        'versions',
    ];

    /**
     * {@inheritdoc}
     */
    public static $validWithRelations = [
        'latest_version',
        'team',
    ];

    /**
     * {@inheritdoc}
     */
    public static $modelRelations = [
        'latest_version' => AddonVersion::class,
        'team'           => Team::class,
    ];

    public function fixRelations()
    {
        parent::fixRelations();

        if (isset($this->attributes['price'])) {
            $this->attributes['price']['original']['amount'] = $this->attributes['price']['original']['amount'] / 100;
            $this->attributes['price']['purchase']['amount'] = $this->attributes['price']['purchase']['amount'] / 100;
        }
    }
}
