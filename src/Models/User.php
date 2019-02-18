<?php

namespace GmodStore\API\Models;

use GmodStore\API\Client;
use GmodStore\API\Model;
use InvalidArgumentException;
use SteamID;

class User extends Model
{

    /**
     * {@inheritdoc}
     */
    protected static $validRelations = [
        'addons',
        'purchases',
        'teams',
        'bans',
    ];

    /**
     * {@inheritdoc}
     */
    protected static $validWithRelations = [
        'group',
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct($attributes = [], Client $client = null)
    {

        if (\is_string($attributes) || \is_int($attributes)) {
            try {
                $xpaw = new SteamID($attributes);
                $attributes = $xpaw->ConvertToUInt64();
            } catch (InvalidArgumentException $e) {
            }
        }

        parent::__construct($attributes, $client);
    }
}
