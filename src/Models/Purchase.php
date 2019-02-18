<?php

namespace GmodStore\API\Models;


use GmodStore\API\Model;

class Purchase extends Model
{
    protected static $validRelations = [
        'addon',
        'transaction',
        'user',
    ];

    protected static $modelRelations = [
        'user'  => User::class,
        'addon' => Addon::class,
    ];

    public function revoke()
    {
        if (!$this->relationLoaded('addonId') && !$this->relationLoaded('user')) {
            throw new \Exception('Purchase resource needs User and at least addon id to revoke');
        }

        $revoked = $this->client->addon($this->addon)->purchases()->update(['user_id' => $this->user->id, 'revoked' => true]);

        return $revoked;
    }
}