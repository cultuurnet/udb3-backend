<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use PHPUnit\Framework\TestCase;

class JobStatusTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_exact_four_statuses(): void
    {
        $statuses = JobStatus::getAllowedValues();

        $expectedStatuses = [
            JobStatus::waiting()->toString(),
            JobStatus::running()->toString(),
            JobStatus::failed()->toString(),
            JobStatus::complete()->toString(),
        ];

        $this->assertEquals($expectedStatuses, $statuses);
    }
}
