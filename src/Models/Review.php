<?php

namespace GmodStore\API\Models;

use GmodStore\API\Model;

class Review extends Model
{
    /**
     * {@inheritdoc}
     */
    public static $validWithRelations = [
        'addon',
        'author',
    ];

    /**
     * {@inheritdoc}
     */
    public static $modelRelations = [
        'addon'  => Addon::class,
        'author' => User::class,
    ];
}
