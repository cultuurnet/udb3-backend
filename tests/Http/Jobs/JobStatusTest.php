<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use PHPUnit\Framework\TestCase;

class JobStatusTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_exact_four_statuses()
    {
        $statuses = JobStatus::getAllowedValues();

        $expectedStatuses = [
            JobStatus::WAITING()->toString(),
            JobStatus::RUNNING()->toString(),
            JobStatus::FAILED()->toString(),
            JobStatus::COMPLETE()->toString(),
        ];

        $this->assertEquals($expectedStatuses, $statuses);
    }
}
