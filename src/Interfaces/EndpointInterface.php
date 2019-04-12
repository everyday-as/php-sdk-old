<?php

namespace GmodStore\API\Interfaces;

interface EndpointInterface
{
    /**
     * @param int|null $id
     *
     * @return \GmodStore\API\Collection|\GmodStore\API\Interfaces\ModelInterface
     */
    public function get($id = null);
}