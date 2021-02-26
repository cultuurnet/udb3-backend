<?php

namespace CultuurNet\UDB3\Broadway\CommandHandling\Validation;

interface CommandValidatorInterface
{
    /**
     * @throws \Exception
     * @return void
     */
    public function validate($command);
}
