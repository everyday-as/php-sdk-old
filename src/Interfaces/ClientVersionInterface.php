<?php

namespace GmodStore\API\Interfaces;

use GmodStore\API\Client;
use GmodStore\API\Collection;
use GmodStore\API\Models\Addon;
use GmodStore\API\Models\Coupon;
use GmodStore\API\Models\User;

interface ClientVersionInterface
{
    const URL_BASE = 'https://api.gmodstore.com';

    /**
     * VersionClientInterface constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client);

    /**
     * Get the version name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the base URL for the API version.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Get the current API url being used.
     *
     * @return string
     */
    public function getCurrentUrl(): string;

    /**
     * @param string $model
     *
     * @return mixed
     */
    public function setModel($model);

    /**
     * Tell the client to get a resource.
     *
     * @return Collection
     */
    public function get(): Collection;

    /**
     * Tell the client to create a resource.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function create(array $data = []);

    /**
     * Tell the client to update a resource.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function update(array $data = []);

    /**
     * Tell the client to delete a resource.
     *
     * @return mixed
     */
    public function delete();

    /*** Resource Endpoints ***/

    /**
     * Setup the client to use the addons endpoint.
     *
     * @return self
     */
    public function addons();

    /**
     * Setup the client for specific addon.
     *
     * @param int $id
     *
     * @return self
     */
    public function addon($id);

    /**
     * Get a single Addon resource.
     *
     * @param $id
     *
     * @return Addon
     */
    public function getAddon($id): Addon;

    /**
     * Get a Collection of Addon when given list of ids.
     *
     * @param mixed ...$ids
     *
     * @return Collection
     */
    public function getAddons(...$ids): Collection;

    /**
     * Get the addons the owner of the apu key is authored on.
     *
     * @return Collection
     */
    public function getMyAddons(): Collection;

    /**
     * @param int $addonId
     *
     * @return Collection
     */
    public function getAddonPurchases($addonId): Collection;

    /**
     * @param int $addonId
     *
     * @return Collection
     */
    public function getAddonCoupons($addonId): Collection;

    /**
     * @param int $addonId
     * @param int $id
     *
     * @return Coupon
     */
    public function getAddonCoupon($addonId, $id): Coupon;

    /**
     * @param int|string $user
     *
     * @return User
     */
    public function getUser($user): User;

    /**
     * @param int|string|User $user
     *
     * @return Collection
     */
    public function getUserPurchases($user): Collection;
}
