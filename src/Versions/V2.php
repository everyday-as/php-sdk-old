<?php

namespace GmodStore\API\Versions;

use GmodStore\API\ClientVersion;
use GmodStore\API\Collection;
use GmodStore\API\Exceptions\EndpointException;
use GmodStore\API\Model;
use GmodStore\API\Models\Addon;
use GmodStore\API\Models\Coupon;
use GmodStore\API\Models\Purchase;
use GmodStore\API\Models\User;
use InvalidArgumentException;

class V2 extends ClientVersion
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return __CLASS__;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(): string
    {
        return self::URL_BASE.'/v2';
    }

    public function get(): Collection
    {
        $this->client->setGuzzleOption('query', ['with' => implode(',', $this->client->getWith())]);
        $response = $this->client->setMethod('get')->send();
        $this->client->setGuzzleOption('query', []);

        $data = \json_decode($response->getBody()->getContents(), true);
        $data = $data['data'] ?? [];

        return $this->client->parseData($data);
    }

    /**
     * Tell the client to create a resource.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function create(array $data = [])
    {
        // TODO: Implement create() method.
    }

    /**
     * Tell the client to update a resource.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function update(array $data = [])
    {
        $this->client->setGuzzleOption('query', []);
        $this->client->setGuzzleOption('form_params', $data);
        $response = $this->client->setMethod('post')->send();

        $data = \json_decode($response->getBody()->getContents());
        $data = $data->data ?? [];

        return $this->client->parseData($data);
    }

    /**
     * Tell the client to delete a resource.
     *
     * @return mixed
     */
    public function delete()
    {
        // TODO: Implement delete() method.
    }

    /**
     * {@inheritdoc}
     */
    public function addons(): self
    {
        $this->client->setEndpoint('addons')->setEndpointUrl($this->getUrl().'/addons');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addon($addon): self
    {
        $addonId = $addon->id ?? $addon;

        $this->addons()->client->appendEndpointUrl('/:addon')->setEndpointParam('addon', $addonId);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function getAddon($id): Addon
    {
        return (new Addon($this->addon($id)->get(), $this->client))->forceExists()->fixRelations();
    }

    public function getAddons(...$ids): Collection
    {
        if (is_array($ids[0])) {
            $ids = $ids[0];
        }

        $length = count($ids);
        $addons = [];

        for ($i = 0; $i < $length; $i++) {
            $addons[] = $this->getAddon($ids[$i]);
        }

        return new Collection($addons);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function getMyAddons(): Collection
    {
        $addons = $this->addons()->get();

        if (!$addons->isEmpty()) {
            $length = \count($addons);
            for ($i = 0; $i < $length; $i++) {
                $addons[$i] = (new Addon($addons[$i], $this->client))->forceExists()->fixRelations();
            }
        }

        return $addons;
    }

    /**
     * Set the coupons sub endpoint.
     *
     * @throws EndpointException
     * @throws \Exception
     *
     * @return $this
     */
    public function coupons(): self
    {
        if (($currentEndpoint = $this->client->getEndpoint()) !== 'addons') {
            throw new EndpointException($currentEndpoint.' does not have a coupons endpoint');
        }

        $this->client->setEndpoint($currentEndpoint.'.coupons')->appendEndpointUrl('/coupons');

        return $this;
    }

    /**
     * Setup client to retrieve an Addon Coupon resource.
     *
     * @param $id
     *
     * @throws \GmodStore\API\Exceptions\EndpointException
     *
     * @return $this
     */
    public function coupon($id): self
    {
        if (!\is_numeric($id)) {
            throw new InvalidArgumentException('Coupon ID must be an integer');
        }

        $this->coupons();

        $this->client->appendEndpointUrl('/:coupon')->setEndpointParam('coupon', $id);

        return $this;
    }

    /**
     * Sets the purchases sub endpoint.
     *
     * @throws EndpointException
     * @throws \Exception
     *
     * @return $this
     */
    public function purchases(): self
    {
        if (!\in_array($currentEndpoint = $this->client->getEndpoint(), ['addons', 'users'])) {
            throw new EndpointException($currentEndpoint.' does not have a purchases endpoint');
        }

        $this->client->setEndpoint($currentEndpoint.'.purchases')->appendEndpointUrl('/purchases');

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws EndpointException
     * @throws \Exception
     */
    public function getAddonPurchases($addon): Collection
    {
        $addonId = $addon;

        if ($addon instanceof Model) {
            $addonId = $addon->id;
        }

        $purchases = $this->addon($addonId)->purchases()->get();

        $length = count($purchases);

        for ($i = 0; $i < $length; $i++) {
            $purchases[$i] = (new Purchase($purchases[$i], $this->client))->forceExists();

            if ($addon instanceof Addon) {
                $purchases[$i]->setRelation('addon', (clone $addon)->removeClient()->removeRelations());
            }

            $purchases[$i]->fixRelations();
        }

        return $purchases;
    }

    /**
     * @param int $addonId
     *
     * @throws \Exception
     *
     * @return Collection
     */
    public function getAddonCoupons($addon): Collection
    {
        $addonId = $addon;

        if ($addon instanceof Model) {
            $addonId = $addon->id;
        }

        $this->addon($addonId)->coupons();
        $coupons = $this->get();

        if (!$coupons->isEmpty()) {
            $length = count($coupons);
            for ($i = 0; $i < $length; $i++) {
                $coupons[$i] = new Coupon($coupons[$i]);
            }
        }

        return $coupons;
    }

    /**
     * @param int $addonId
     * @param int $id
     *
     * @return mixed
     */
    public function getAddonCoupon($addonId, $id): Coupon
    {
        $this->addon($addonId)->client->appendEndpointUrl('/coupons/:coupon')->setEndpointParam('coupon', $id);
        // TODO: Implement getAddonCoupon() method.
    }

    /**
     * @param $addonId
     *
     * @return Collection
     */
    public function getAddonVersions($addonId)
    {
        $this->addon($addonId)->client->appendEndpointUrl('/versions');

        return $this->get();
    }

    /**
     * @param int|string $user
     *
     * @return User
     */
    public function getUser($user): User
    {
        // TODO: Implement getUser() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPurchases($user): Collection
    {
        // TODO: Implement getUserPurchases() method.
    }
}
