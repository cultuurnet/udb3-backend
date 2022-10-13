<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;

abstract class AbstractContextAwareCommandBus implements CommandBus, ContextAwareInterface
{
}
