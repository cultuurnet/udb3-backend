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
            ],
        ];

        $this->validator->validate($data);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     * @dataProvider invalidData
     */
    public function it_fails_on_invalid_data(array $data, array $expectedMessages): void
    {
        $validationMessages = [];

        try {
            $this->validator->validate($data);
        } catch (DataValidationException $e) {
            $validationMessages = $e->getValidationMessages();
        }

        $this->assertEquals(
            $expectedMessages,
            $validationMessages
        );
    }

    public function invalidData(): array
    {
        return [
            'empty payload' => [
                [],
                [
                    'name' => 'Required but could not be found',
                    'eventIds' => 'Required but could not be found',
                ],
            ],
            'Without events' => [
                [
                    'name' => 'foo',
                ],
                [
                    'eventIds' => 'Required but could not be found',
                ],
            ],
            'With empty events' => [
                [
                    'name' => 'foo',
                    'eventIds' => [],
                ],
                [
                    'eventIds' => 'At least two events should be provided',
                ],
            ],
            'With one event' => [
                [
                    'name' => 'foo',
                    'eventIds' => [
                        Uuid::uuid4()->toString(),
                    ],
                ],
                [
                    'eventIds' => 'At least two events should be provided',
                ],
            ],
            'Without name' => [
                [
                    'eventIds' => [
                        Uuid::uuid4()->toString(),
                        Uuid::uuid4()->toString(),
                    ],
                ],
                [
                    'name' => 'Required but could not be found',
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
                [
                    'name' => 'Cannot be empty',
                ],
            ],
        ];
    }
}
