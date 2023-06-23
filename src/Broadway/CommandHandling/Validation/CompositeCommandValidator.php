<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\CommandHandling\Validation;

class CompositeCommandValidator implements CommandValidatorInterface
{
    /**
     * @var CommandValidatorInterface[]
     */
    private array $commandValidators;

    public function __construct(CommandValidatorInterface ...$commandValidators)
    {
        $this->commandValidators = $commandValidators;
    }

    public function register(CommandValidatorInterface $commandValidator): void
    {
        $this->commandValidators[] = $commandValidator;
    }

    public function validate(object $command): void
    {
        foreach ($this->commandValidators as $commandValidator) {
            $commandValidator->validate($command);
        }
    }
}
