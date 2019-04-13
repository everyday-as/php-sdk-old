<?php

namespace GmodStore\API\Models;

use GmodStore\API\Model;

class PrimaryAuthor extends Model
{
    /**
     * {@inheritdoc}
     */
    public static $validWithRelations = [
        'user',
    ];

    /**
     * {@inheritdoc}
     */
    public static $modelRelations = [
        'user' => User::class,
    ];
}
