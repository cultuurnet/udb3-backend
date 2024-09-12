<?php

declare(strict_types=1);

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
    **/
    public function slug($string): string;
}
