<?php

namespace GmodStore\API\Models;

use GmodStore\API\Model;

class Purchase extends Model
{
    public static $modelRelations = [
        'user'  => User::class,
        'addon' => Addon::class,
    ];
}
