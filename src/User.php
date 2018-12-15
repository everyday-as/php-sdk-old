<?php

namespace kanalumaddela\GmodStoreAPI;

use Exception;
use InvalidArgumentException;
use SteamID;

class User extends Model
{
    /**
     * {@inheritdoc}
     */
    public static $endpoint = 'users';

    /**
     * User constructor. Check if argument is a steamid or not and convert it.
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        if (\is_string($attributes) || \is_int($attributes)) {
            try {
                $xpaw = new SteamID($attributes);
                $attributes = $xpaw->ConvertToUInt64();
            } catch (InvalidArgumentException $e) {
            }
        }

        parent::__construct($attributes);
    }

    public function getBans()
    {
        if (\is_null($bans = $this->newRequest()->getBans())) {
            $bans = [];
        }

        $this->setRelation('bans', $bans);

        return $bans;
    }

    /**
     * Get a user's purchases and optionally retrieve the addon along with it.
     *
     * @param bool $withAddon
     *
     * @throws Exception
     *
     * @return array|mixed|null
     */
    public function getPurchases($withAddon = false)
    {
        if (!in_array('addon', $this->withRelations) && $withAddon === true) {
            $this->withRelations[] = 'addon';
            $this->newRequest()->with($this->withRelations);
        }

        if (\is_null($purchases = $this->newRequest()->getPurchases())) {
            $purchases = [];
        }

        if (($length = \count($purchases)) > 0) {
            for ($i = 0; $i < $length; $i++) {
                $purchase = $purchases[$i];

                if (isset($purchase->addon)) {
                    $purchase->addon = (new Addon($purchase->addon))->setClient($this->getClient())->with($this->withRelations)->forceExists()->fixRelations();
                }

                $user = clone $this;
                $purchase->user = $user->removeRelations();

                $purchases[$i] = $purchase;
            }
        }

        $this->setRelation('purchases', $purchases);

        return $purchases;
    }

    /**
     * Get a user's teams.
     *
     * @throws Exception
     *
     * @return array|mixed|null
     */
    public function getTeams()
    {
        if (\is_null($teams = $this->newRequest()->getTeams())) {
            $teams = [];
        }

        if (($length = \count($teams)) > 0) {
            for ($i = 0; $i < $length; $i++) {
                $teams[$i] = (new Team($teams[$i]))->setClient($this->getClient())->with($this->withRelations)->forceExists()->fixRelations();
            }
        }

        $this->setRelation('teams', $teams);

        return $teams;
    }
}
