<?php

namespace CultuurNet\Broadway\CommandHandling\Validation;

class CompositeCommandValidator implements CommandValidatorInterface
{
    /**
     * @var CommandValidatorInterface[]
     */
    private $commandValidators;

    /**
     * @param CommandValidatorInterface[] $commandValidators
     */
    public function __construct(CommandValidatorInterface ...$commandValidators)
    {
        $this->commandValidators = $commandValidators;
    }

    /**
     * @param CommandValidatorInterface $commandValidator
     * @return void
     */
    public function register(CommandValidatorInterface $commandValidator)
    {
        $this->commandValidators[] = $commandValidator;
    }

    /**
     * @inheritdoc
     */
    public function validate($command)
    {
        foreach ($this->commandValidators as $commandValidator) {
            $commandValidator->validate($command);
        }
    }
}
