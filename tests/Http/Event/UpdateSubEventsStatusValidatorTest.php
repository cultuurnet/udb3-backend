<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\Deserializer\DataValidationException;
use PHPUnit\Framework\TestCase;

class UpdateSubEventsStatusValidatorTest extends TestCase
{
    /**
     * @var UpdateSubEventsStatusValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new UpdateSubEventsStatusValidator();
    }

    /**
     * @test
     */
    public function it_allows_valid_data(): void
    {
        $data = [
            [
                'id' => 1,
                'status' => [
                    'type' => 'TemporarilyUnavailable',
                    'reason' => [
                        'nl' => 'Niet vandaag',
                        'fr' => "Pas aujourd'hui",
                    ],
                ],
            ],
            [
                'id' => 2,
                'status' => [
                    'type' => 'Unavailable',
                    'reason' => [
                        'nl' => 'Nee',
                        'fr' => 'Non',
                    ],
                ],
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


    public function getInvalidData()
    {
        return [
            'empty event' => [
                [[]],
                [
                    '[0].id' => 'Required but could not be found',
                    '[0].status.type' => 'Required but could not be found',
                ],
            ],
            'without id' => [
                [
                    [
                        'status' => [
                            'type' => 'Unavailable',
                        ],
                    ]
                ],
                [
                    '[0].id' => 'Required but could not be found',
                ],
            ],
            'without status' => [
                [
                    [
                        'id' => 0,
                    ],
                ],
                [
                    '[0].status.type' => 'Required but could not be found',
                ],
            ],
            'invalid id' => [
                [
                    [
                        'id' => 'DefinitelyNotAnId',
                        'status' => [
                            'type' => 'Unavailable',
                        ],
                    ]
                ],
                [
                    '[0].id' => 'Should be an integer',
                ],
            ],
            'invalid status' => [
                [
                    [
                        'id' => 0,
                        'status' => [
                            'type' => 'DefinitelyNotAValidStatus',
                        ],
                    ]
                ],
                [
                    '[0].status.type' => 'Invalid status provided',
                ],
            ],
            'empty reason' => [
                [
                    [
                        'id' => 0,
                        'status' => [
                            'type' => 'Unavailable',
                            'reason' => [
                                'nl' => '',
                            ],
                        ],
                    ]
                ],
                [
                    '[0].status.reason.nl' => 'Cannot be empty',
                ],
            ],
            'empty second event' => [
                [
                    [
                        'id' => 0,
                        'status' => [
                            'type' => 'Unavailable',
                        ],
                    ],
                    [],
                ],
                [
                    '[1].id' => 'Required but could not be found',
                    '[1].status.type' => 'Required but could not be found',
                ],
            ],
        ];
    }
}
