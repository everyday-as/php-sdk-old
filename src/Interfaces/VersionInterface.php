<?php

namespace GmodStore\API\Interfaces;

interface VersionInterface
{

    /**
     * Base URL for the endpoints
     *
     * @var string
     */
    const URL_BASE = 'https://api.gmodstore.com';

    /**
     * Get the name of this API version
     *
     * @return string
     */
    public function getName();

    /**
     * Get the API version's URL endpoint
     *
     * @return string
     */
    public function getUrl();

    /**
     * Get the endpoint URL for the Addon resource
     *
     * @param int $id
     *
     * @return string
     */
    public function addon($id);

    /**
     * Get the endpoint URL for the User resource
     *
     * @param int|string $id
     *
     * @return string
     */
    public function user($id);
}
