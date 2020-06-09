<?php

namespace CultuurNet\UDB3\Http\Productions;

use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

class CreateProductionValidatorTest extends TestCase
{
    /**
     * @test
     */
    public function it_allows_valid_data(): void
    {
        $data = [
            'name' => 'foo',
            'eventIds' => [
                Uuid::uuid4()->toString(),
                Uuid::uuid4()->toString(),
            ]
        ];
    }

    /**
     * @test
     */
    public function it_requires_at_least_two_events(): void
    {
        $dataWithoutEvents = [
            'name' => 'foo',
        ];

        $dataWithEmptyEvents = [
            'name' => 'foo',
            'eventIds' => []
        ];

        $dataWithOneEvent = [
            'name' => 'foo',
            'eventIds' => [
                Uuid::uuid4()->toString(),
                Uuid::uuid4()->toString(),
            ]
        ];
    }

    /**
     * @test
     */
    public function it_requires_a_name()
    {
        $dataWithoutName = [
            'eventIds' => [
                Uuid::uuid4()->toString(),
                Uuid::uuid4()->toString(),
            ]
        ];

        $dataWithEmptyName = [
            'name' => '   ',
            'eventIds' => [
                Uuid::uuid4()->toString(),
                Uuid::uuid4()->toString(),
            ]
        ];
    }
}
