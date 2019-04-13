<?php

namespace GmodStore\API\Interfaces;

interface EndpointInterface
{
    /**
     * @param array|int|null $id
     *
     * @return \GmodStore\API\Collection|\GmodStore\API\Interfaces\ModelInterface|array
     */
    public function get($id = null);
}
