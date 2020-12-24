<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\Deserializer\DataValidationException;
use PHPUnit\Framework\TestCase;

class UpdateStatusValidatorTest extends TestCase
{
    /**
     * @var UpdateStatusValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new UpdateStatusValidator();
    }

    /**
     * @test
     */
    public function it_allows_valid_data(): void
    {
        $data = [
            'type' => 'TemporarilyUnavailable',
            'reason' => [
                'nl' => 'Niet vandaag',
                'fr' => "Pas aujourd'hui",
            ],
        ];

        $this->validator->validate($data);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     * @dataProvider getInvalidData
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

    public function getInvalidData(): array
    {
        return [
            'empty body' => [
                [],
                [
                    'type' => 'Required but could not be found',
                ],
            ],
            'invalid type' => [
                [
                    'type' => 'foo',
                ],
                [
                    'type' => 'Invalid type provided',
                ],
            ],
            'empty reason' => [
                [
                    'type' => 'Unavailable',
                    'reason' => [
                        'nl' => '',
                    ],
                ],
                [
                    'reason.nl' => 'Cannot be empty',
                ],
            ],
            'invalid reason' => [
                [
                    'type' => 'Unavailable',
                    'reason' => 'Should be an object instead, not a string',
                ],
                [
                    'reason' => 'Should be an object with language codes as properties and string values',
                ]
            ],
            'invalid reason language' => [
                [
                    'type' => 'Unavailable',
                    'reason' => ['foo' => 'bar'],
                ],
                [
                    'reason.foo' => 'Language key should be a string of exactly 2 characters',
                ]
            ]
        ];
    }
}
