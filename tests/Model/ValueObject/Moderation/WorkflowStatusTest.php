<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Moderation;

use PHPUnit\Framework\TestCase;

class WorkflowStatusTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_five_possible_values()
    {
        $readyForValidation = WorkflowStatus::READY_FOR_VALIDATION();
        $approved = WorkflowStatus::APPROVED();
        $rejected = WorkflowStatus::REJECTED();
        $draft = WorkflowStatus::DRAFT();
        $deleted = WorkflowStatus::DELETED();

        $this->assertEquals('READY_FOR_VALIDATION', $readyForValidation->toString());
        $this->assertEquals('APPROVED', $approved->toString());
        $this->assertEquals('REJECTED', $rejected->toString());
        $this->assertEquals('DRAFT', $draft->toString());
        $this->assertEquals('DELETED', $deleted->toString());
    }
}
