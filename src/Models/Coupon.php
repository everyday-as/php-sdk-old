<?php

namespace GmodStore\API\Models;

use GmodStore\API\Model;

class Coupon extends Model
{
    /**
     * {@inheritdoc}
     */
    public static $modelRelations = [
        'addon' => Addon::class,
    ];
}
