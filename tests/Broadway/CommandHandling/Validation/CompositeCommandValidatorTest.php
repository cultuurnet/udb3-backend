<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\CommandHandling\Validation;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompositeCommandValidatorTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_call_each_injected_validator_once_when_asked_to_validate_a_command(): void
    {
        $command = (object) ['do' => 'something'];

        /** @var CommandValidatorInterface&MockObject $validator1 */
        $validator1 = $this->createMock(CommandValidatorInterface::class);
        $validator1->expects($this->once())
            ->method('validate')
            ->with($command);

        /** @var CommandValidatorInterface&MockObject $validator2 */
        $validator2 = $this->createMock(CommandValidatorInterface::class);
        $validator2->expects($this->once())
            ->method('validate')
            ->with($command);

        $compositeValidator = new CompositeCommandValidator($validator1, $validator2);

        $compositeValidator->validate($command);
    }

    /**
     * @test
     */
    public function it_should_be_able_to_register_more_validators_after_construction(): void
    {
        $command = (object) ['do' => 'something'];

        /** @var CommandValidatorInterface&MockObject $validator1 */
        $validator1 = $this->createMock(CommandValidatorInterface::class);
        $validator1->expects($this->once())
            ->method('validate')
            ->with($command);

        /** @var CommandValidatorInterface&MockObject $validator2 */
        $validator2 = $this->createMock(CommandValidatorInterface::class);
        $validator2->expects($this->once())
            ->method('validate')
            ->with($command);

        /** @var CommandValidatorInterface&MockObject $validator3 */
        $validator3 = $this->createMock(CommandValidatorInterface::class);
        $validator3->expects($this->once())
            ->method('validate')
            ->with($command);

        $compositeValidator = new CompositeCommandValidator($validator1, $validator2);
        $compositeValidator->register($validator3);

        $compositeValidator->validate($command);
    }
}
