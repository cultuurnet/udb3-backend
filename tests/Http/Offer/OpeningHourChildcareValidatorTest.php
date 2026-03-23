<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use PHPUnit\Framework\TestCase;

final class OpeningHourChildcareValidatorTest extends TestCase
{
    private OpeningHourChildcareValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new OpeningHourChildcareValidator();
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_childcare_is_absent(): void
    {
        $errors = $this->validator->validate((object) [
            'openingHours' => [
                (object)['opens' => '09:00', 'closes' => '17:00'],
            ],
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_opening_hours_is_absent(): void
    {
        $errors = $this->validator->validate((object) []);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_childcare_times_are_valid(): void
    {
        $errors = $this->validator->validate((object) [
            'openingHours' => [
                (object)['opens' => '09:00', 'closes' => '17:00', 'childcare' => (object)['start' => '08:00', 'end' => '18:00']],
            ],
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     * @dataProvider invalidChildcareStartProvider
     */
    public function it_returns_error_when_childcare_start_is_not_before_opens(
        string $childcareStart
    ): void {
        $errors = $this->validator->validate((object) [
            'openingHours' => [
                (object)['opens' => '09:00', 'closes' => '17:00', 'childcare' => (object)['start' => $childcareStart, 'end' => '18:00']],
            ],
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('/openingHours/0/childcare/start', $errors[0]->getJsonPointer());
        $this->assertSame('childcare.start must be before opens', $errors[0]->getError());
    }

    public function invalidChildcareStartProvider(): array
    {
        return [
            'equal to opens' => ['09:00'],
            'after opens'    => ['10:00'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidChildcareEndProvider
     */
    public function it_returns_error_when_childcare_end_is_not_after_closes(
        string $childcareEnd
    ): void {
        $errors = $this->validator->validate((object) [
            'openingHours' => [
                (object)['opens' => '09:00', 'closes' => '17:00', 'childcare' => (object)['start' => '08:00', 'end' => $childcareEnd]],
            ],
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('/openingHours/0/childcare/end', $errors[0]->getJsonPointer());
        $this->assertSame('childcare.end must be after closes', $errors[0]->getError());
    }

    public function invalidChildcareEndProvider(): array
    {
        return [
            'equal to closes' => ['17:00'],
            'before closes'   => ['16:00'],
        ];
    }

    /**
     * @test
     */
    public function it_returns_both_errors_when_both_childcare_times_are_invalid(): void
    {
        $errors = $this->validator->validate((object) [
            'openingHours' => [
                (object)['opens' => '09:00', 'closes' => '17:00', 'childcare' => (object)['start' => '10:00', 'end' => '16:00']],
            ],
        ]);

        $this->assertCount(2, $errors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_opens_is_absent(): void
    {
        $errors = $this->validator->validate((object) [
            'openingHours' => [
                (object)['closes' => '17:00', 'childcare' => (object)['start' => '10:00', 'end' => '18:00']],
            ],
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_closes_is_absent(): void
    {
        $errors = $this->validator->validate((object) [
            'openingHours' => [
                (object)['opens' => '09:00', 'childcare' => (object)['start' => '08:00', 'end' => '16:00']],
            ],
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_all_opening_hours_are_valid(): void
    {
        $errors = $this->validator->validate((object) [
            'openingHours' => [
                (object)['opens' => '09:00', 'closes' => '17:00', 'childcare' => (object)['start' => '08:00', 'end' => '18:00']],
                (object)['opens' => '10:00', 'closes' => '16:00'],
            ],
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_collects_errors_from_multiple_opening_hours_with_correct_json_pointers(): void
    {
        $errors = $this->validator->validate((object) [
            'openingHours' => [
                (object)['opens' => '09:00', 'closes' => '17:00', 'childcare' => (object)['start' => '09:00', 'end' => '18:00']],
                (object)['opens' => '10:00', 'closes' => '16:00', 'childcare' => (object)['start' => '08:00', 'end' => '16:00']],
            ],
        ]);

        $this->assertCount(2, $errors);
        $this->assertSame('/openingHours/0/childcare/start', $errors[0]->getJsonPointer());
        $this->assertSame('/openingHours/1/childcare/end', $errors[1]->getJsonPointer());
    }
}
