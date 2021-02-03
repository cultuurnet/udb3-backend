<?php

namespace CultuurNet\UDB3\Broadway\CommandHandling\Validation;

interface CommandValidatorInterface
{
    /**
     * @param mixed $command
     * @throws \Exception
     * @return void
     */
    public function validate($command);
}
