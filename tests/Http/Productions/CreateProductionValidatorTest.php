<?php

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\Deserializer\DataValidationException;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

class CreateProductionValidatorTest extends TestCase
{
    /**
     * @var CreateProductionValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new CreateProductionValidator();
    }

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

        $this->validator->validate($data);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     * @dataProvider invalidData
     */
    public function it_fails_on_invalid_data(array $data): void
    {
        $this->expectException(DataValidationException::class);
        $this->validator->validate($data);
    }

    public function invalidData(): array
    {
        return [
            'Without events' => [
                [
                    'name' => 'foo',
                ],
            ],
            'With empty events' => [
                [
                    'name' => 'foo',
                    'eventIds' => [],
                ],
            ],
            'With one event' => [
                [
                    'name' => 'foo',
                    'eventIds' => [
                        Uuid::uuid4()->toString(),
                    ],
                ],
            ],
            'Without name' => [
                [
                    'eventIds' => [
                        Uuid::uuid4()->toString(),
                        Uuid::uuid4()->toString(),
                    ],
                ],
            ],
            'With empty name' => [
                [
                    'name' => '   ',
                    'eventIds' => [
                        Uuid::uuid4()->toString(),
                        Uuid::uuid4()->toString(),
                    ],
                ],
            ],
        ];
    }
}
