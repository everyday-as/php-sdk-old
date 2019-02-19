<?php

namespace GmodStore\API\Models;

use GmodStore\API\Model;

class Team extends Model
{
    /**
     * {@inheritdoc}
     */
    public static $endpoint = 'teams';

    /**
     * {@inheritdoc}
     */
    protected static $validRelations = [
        'users',
    ];

    /**
     * {@inheritdoc}
     */
    protected static $validWithRelations = [
        'primaryAuthor',
        'primary_author',
    ];

    /**
     * {@inheritdoc}
     */
    protected static $modelRelations = [
        'primaryAuthor'  => PrimaryAuthor::class,
        'primary_author' => PrimaryAuthor::class,
    ];
}
