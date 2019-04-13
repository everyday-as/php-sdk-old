<?php

namespace GmodStore\API\Models;

use GmodStore\API\Model;

class Team extends Model
{
    /**
     * {@inheritdoc}
     */
    public static $validRelations = [
        'users',
    ];

    /**
     * {@inheritdoc}
     */
    public static $validWithRelations = [
        'primaryAuthor',
        'primary_author',
    ];

    /**
     * {@inheritdoc}
     */
    public static $modelRelations = [
        'primaryAuthor'  => PrimaryAuthor::class,
        'primary_author' => PrimaryAuthor::class,
    ];
}
