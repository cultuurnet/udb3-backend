<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

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
                'status' => 'EventPostponed',
                'reason' => [
                    'nl' => 'Niet vandaag',
                    'fr' => "Pas aujourd'hui",
                ],
            ],
            [
                'id' => 2,
                'status' => 'EventCancelled',
                'reason' => [
                    'nl' => 'Nee',
                    'fr' => 'Non',
                ],
            ],
        ];

        $this->validator->validate($data);
        $this->addToAssertionCount(1);
    }
}
