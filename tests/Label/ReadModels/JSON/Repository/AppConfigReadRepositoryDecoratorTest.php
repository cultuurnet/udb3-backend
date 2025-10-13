<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AppConfigReadRepositoryDecoratorTest extends TestCase
{
    /** @var ReadRepositoryInterface&MockObject */
    private ReadRepositoryInterface $decoratee;
    private AppConfigReadRepositoryDecorator $appConfigReadRepositoryDecorator;

    protected function setUp(): void
    {
        $this->decoratee = $this->createMock(ReadRepositoryInterface::class);
        $this->appConfigReadRepositoryDecorator = new AppConfigReadRepositoryDecorator(
            $this->decoratee,
            [
                'clientWithLabels@clients' => [
                    'labels' => ['privateLabel'],
                ],
                'clientWithZeroLabels@clients' => [
                    'labels' => [],
                ],
                'clientWithoutLabelsKey@clients' => [],
            ]
        );
    }

    /**
     * @test
     */
    public function it_delegates_can_use_label_check_to_decoratee_first_and_returns_true_if_that_says_it_is_okay(): void
    {
        $userId = 'clientWithLabels@clients';
        $label = 'publicLabel';

        $this->decoratee->expects($this->once())
            ->method('canUseLabel')
            ->with($userId, $label)
            ->willReturn(true);

        $canUseLabel = $this->appConfigReadRepositoryDecorator->canUseLabel($userId, $label);
        $this->assertTrue($canUseLabel);
    }

    /**
     * @test
     */
    public function it_returns_false_if_the_decorator_returns_false_and_the_client_is_not_defined_in_the_config(): void
    {
        $userId = 'unknownClient@clients';
        $label = 'privateLabel';

        $this->decoratee->expects($this->once())
            ->method('canUseLabel')
            ->with($userId, $label)
            ->willReturn(false);

        $canUseLabel = $this->appConfigReadRepositoryDecorator->canUseLabel($userId, $label);
        $this->assertFalse($canUseLabel);
    }

    /**
     * @test
     */
    public function it_returns_false_if_the_decorator_returns_false_and_the_client_has_no_labels_key_in_the_config(): void
    {
        $userId = 'clientWithoutLabelsKey@clients';
        $label = 'privateLabel';

        $this->decoratee->expects($this->once())
            ->method('canUseLabel')
            ->with($userId, $label)
            ->willReturn(false);

        $canUseLabel = $this->appConfigReadRepositoryDecorator->canUseLabel($userId, $label);
        $this->assertFalse($canUseLabel);
    }

    /**
     * @test
     */
    public function it_returns_false_if_the_decorator_returns_false_and_the_client_has_no_labels_in_the_config(): void
    {
        $userId = 'clientWithZeroLabels@clients';
        $label = 'privateLabel';

        $this->decoratee->expects($this->once())
            ->method('canUseLabel')
            ->with($userId, $label)
            ->willReturn(false);

        $canUseLabel = $this->appConfigReadRepositoryDecorator->canUseLabel($userId, $label);
        $this->assertFalse($canUseLabel);
    }

    /**
     * @test
     */
    public function it_returns_false_if_the_decorator_returns_false_and_the_client_does_not_have_the_label_in_the_config(): void
    {
        $userId = 'clientWithLabels@clients';
        $label = 'privateLabelNotAllowed';

        $this->decoratee->expects($this->once())
            ->method('canUseLabel')
            ->with($userId, $label)
            ->willReturn(false);

        $canUseLabel = $this->appConfigReadRepositoryDecorator->canUseLabel($userId, $label);
        $this->assertFalse($canUseLabel);
    }

    /**
     * @test
     */
    public function it_returns_true_if_the_decorator_returns_false_but_the_client_has_the_label_in_the_config(): void
    {
        $userId = 'clientWithLabels@clients';
        $label = 'privateLabel';

        $this->decoratee->expects($this->once())
            ->method('canUseLabel')
            ->with($userId, $label)
            ->willReturn(false);

        $canUseLabel = $this->appConfigReadRepositoryDecorator->canUseLabel($userId, $label);
        $this->assertTrue($canUseLabel);
    }

    /**
     * @test
     */
    public function it_returns_true_if_the__client_has_the_label_in_a_different_case_in_the_config(): void
    {
        $userId = 'clientWithLabels@clients';
        $label = 'PRIVATElABEL';

        $this->decoratee->expects($this->once())
            ->method('canUseLabel')
            ->with($userId, $label)
            ->willReturn(false);

        $canUseLabel = $this->appConfigReadRepositoryDecorator->canUseLabel($userId, $label);
        $this->assertTrue($canUseLabel);
    }
}
