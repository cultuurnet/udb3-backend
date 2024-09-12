<?php

declare(strict_types=1);

/**
 * @file
 * Contains \Cultuurnet\UDB3\EntityServiceInterface.
 */

namespace CultuurNet\UDB3;

interface SluggerInterface
{
    public function slug(string $string): string;
}
