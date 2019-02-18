<?php

namespace GmodStore\API\Models;

use GmodStore\API\Model;

class Team extends Model
{
    public static $endpoint = 'teams';

    protected static $validRelations = [
        'users',
    ];

    protected static $validWithRelations = [
        'primaryAuthor',
        'primary_author',
        'user',
    ];

    protected static $modelRelations = [
        'user' => User::class,
    ];
}
