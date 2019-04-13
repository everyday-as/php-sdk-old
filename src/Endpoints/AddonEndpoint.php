<?php

namespace GmodStore\API\Endpoints;

use GmodStore\API\Collection;
use GmodStore\API\Endpoint;
use GmodStore\API\Models\Addon;

class AddonEndpoint extends Endpoint
{
    public static $endpointPath = 'addons';

    public static $model = Addon::class;

    public function get($id = null)
    {
        $data = parent::get($id);
        $data = $data['data'] ?? [];

        $model = new Collection();

        if (empty($this->id) && !empty($data)) {
            foreach ($data as $addon) {
                $model[] = new self::$model($addon, $this);
            }
        }

        if (!empty($this->id)) {
            $model = new self::$model($data, $this);
        }

        return $model;
    }
}
