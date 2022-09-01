<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use InvalidArgumentException;
use Pimple;
use Psr\Container\ContainerInterface;

final class PimplePSRContainerBridge implements ContainerInterface
{
    private Pimple $pimple;

    public function __construct(Pimple $pimple)
    {
        $this->pimple = $pimple;
    }

    public function get($id)
    {
        try {
            return $this->pimple->offsetGet($id);
        } catch (InvalidArgumentException $e) {
            throw new PimpleServiceNotFound($e->getMessage());
        }
    }

    public function has($id): bool
    {
        return $this->pimple->offsetExists($id);
    }
}
