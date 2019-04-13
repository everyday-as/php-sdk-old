<?php

namespace GmodStore\API\Models;

use GmodStore\API\Model;

class User extends Model
{
    /**
     * {@inheritdoc}
     */
    public static $validRelations = [
        'addons',
        'purchases',
        'teams',
        'bans',
    ];

    /**
     * {@inheritdoc}
     */
    public static $validWithRelations = [
        'group',
    ];
}
