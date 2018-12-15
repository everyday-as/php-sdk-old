<?php

namespace kanalumaddela\GmodStoreAPI\Interfaces;

use kanalumaddela\GmodStoreAPI\Addon;
use kanalumaddela\GmodStoreAPI\Client;
use kanalumaddela\GmodStoreAPI\Collection;
use kanalumaddela\GmodStoreAPI\User;

interface VersionInterface
{
    /**
     * @param $id
     *
     * @return Client
     */
    public function addon($id);

    /**
     * @param array $ids
     *
     * @return mixed|Collection
     */
    public function addons(array $ids);

    /**
     * @param $id
     *
     * @return Client
     */
    public function user($id);

    /**
     * @param array $ids
     *
     * @return mixed|Collection
     */
    public function users(array $ids);
}
