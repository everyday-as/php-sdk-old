<?php

namespace GmodStore\API\Models;


use GmodStore\API\Model;

class PrimaryAuthor extends Model
{
    /**
     * {@inheritdoc}
     */
    protected static $validRelations = [
        'users',
    ];

    /**
     * {@inheritdoc}
     */
    protected static $modelRelations = [
        'user' => User::class,
    ];

}