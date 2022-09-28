<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Container;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

final class PimpleServiceNotFound extends InvalidArgumentException implements NotFoundExceptionInterface
{
}
