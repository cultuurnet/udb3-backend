<?php

/**
 * @file
 * Contains \Cultuurnet\UDB3\EntityServiceInterface.
 */

namespace CultuurNet\UDB3;

/**
 * Interface for a service performing entity related tasks.
 */
interface EntityServiceInterface
{
    /**
     * Get a single entity by its id.
     *
     * @param string $id
     *   A string uniquely identifying an entity.
     *
     * @return array
     *   An entity array.
     *
     * @throws EntityNotFoundException if an entity can not be found for the given id.
     */
    public function getEntity($id);

    public function iri($id);
}
