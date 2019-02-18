<?php

namespace GmodStore\API\Models;

use GmodStore\API\Model;

class Coupon extends Model
{
    /**
     * {@inheritdoc}
     */
    protected static $validWithRelations = [
        'with',
    ];

    /**
     * {@inheritdoc}
     */
    protected static $modelRelations = [
        'addon' => Addon::class,
    ];

    public function expired()
    {
        // todo
    }

    public function delete($addonId = null)
    {
        // todo
    }
}
