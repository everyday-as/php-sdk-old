<?php

namespace kanalumaddela\GmodStoreAPI\Versions;

use kanalumaddela\GmodStoreAPI\Addon;
use kanalumaddela\GmodStoreAPI\Collection;
use kanalumaddela\GmodStoreAPI\Interfaces\VersionInterface;
use kanalumaddela\GmodStoreAPI\User;

class V1 implements VersionInterface
{



    /**
     * @param $id
     * @return mixed|Addon
     */
    public function addon($id)
    {
        // TODO: Implement addon() method.
    }

    /**
     * @param array $ids
     * @return mixed|Collection
     */
    public function addons(array $ids)
    {
        // TODO: Implement addons() method.
    }

    /**
     * @param $id
     * @return mixed|User
     */
    public function user($id)
    {
        // TODO: Implement user() method.
    }

    /**
     * @param array $ids
     * @return mixed|Collection
     */
    public function users(array $ids)
    {
        // TODO: Implement users() method.
    }
}