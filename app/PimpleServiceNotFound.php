<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

final class PimpleServiceNotFound extends InvalidArgumentException implements NotFoundExceptionInterface
{
}
