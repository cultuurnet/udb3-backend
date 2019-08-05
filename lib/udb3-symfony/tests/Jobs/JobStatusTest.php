<?php

namespace CultuurNet\UDB3\Symfony\Jobs;

class JobStatusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_has_exact_four_statuses()
    {
        $statuses = JobStatus::getConstants();

        $expectedStatuses = [
            JobStatus::WAITING()->getName() => JobStatus::WAITING()->toNative(),
            JobStatus::RUNNING()->getName() => JobStatus::RUNNING()->toNative(),
            JobStatus::FAILED()->getName() => JobStatus::FAILED()->toNative(),
            JobStatus::COMPLETE()->getName() => JobStatus::COMPLETE()->toNative(),
        ];

        $this->assertEquals($expectedStatuses, $statuses);
    }
}
