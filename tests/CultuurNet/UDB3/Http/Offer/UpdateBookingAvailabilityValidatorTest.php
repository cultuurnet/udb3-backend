<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use PHPUnit\Framework\TestCase;

final class UpdateBookingAvailabilityValidatorTest extends TestCase
{
    /**
     * @var UpdateBookingAvailabilityValidator
     */
    private $updateBookingAvailabilityValidator;

    protected function setUp(): void
    {
        $this->updateBookingAvailabilityValidator = new UpdateBookingAvailabilityValidator();
    }

    /**
     * @test
     */
    public function it_allows_valid_data(): void
    {
        $data = [
            'type' => 'Available',
        ];

        $this->updateBookingAvailabilityValidator->validate($data);
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
            $this->updateBookingAvailabilityValidator->validate($data);
        } catch (DataValidationException $e) {
            $validationMessages = $e->getValidationMessages();
        }

        $this->assertEquals($expectedMessages, $validationMessages);
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
        ];
    }
}
