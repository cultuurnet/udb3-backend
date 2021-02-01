<?php

/**
 * @file
 * Contains \Cultuurnet\UDB3\EntityServiceInterface.
 */

namespace CultuurNet\UDB3;

/**
 * Interface for a service performing entity related tasks.
 */
interface SluggerInterface
{
    /**
    * Returns the slug for a given string
    *
    * @param string $string
    *
    * @return string
    **/
    public function slug($string);
}
