<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use PHPUnit\Framework\TestCase;

final class ChildcareTimeValidatorTest extends TestCase
{
    private ChildcareTimeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ChildcareTimeValidator();
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_neither_childcare_field_is_present(): void
    {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_childcare_start_time_is_before_start_date_time(): void
    {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcareStartTime' => '15:00',
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_childcare_end_time_is_after_end_date_time(): void
    {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcareEndTime' => '23:00',
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     * @dataProvider invalidChildcareStartTimeProvider
     */
    public function it_returns_error_when_childcare_start_time_is_not_before_start_date_time(
        string $childcareStartTime
    ): void {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcareStartTime' => $childcareStartTime,
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('/childcareStartTime', $errors[0]->getJsonPointer());
        $this->assertSame('childcareStartTime must be before the time portion of startDate', $errors[0]->getError());
    }

    public function invalidChildcareStartTimeProvider(): array
    {
        return [
            'equal to startDate time' => ['16:00'],
            'after startDate time'    => ['17:00'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidChildcareEndTimeProvider
     */
    public function it_returns_error_when_childcare_end_time_is_not_after_end_date_time(
        string $childcareEndTime
    ): void {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcareEndTime' => $childcareEndTime,
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('/childcareEndTime', $errors[0]->getJsonPointer());
        $this->assertSame('childcareEndTime must be after the time portion of endDate', $errors[0]->getError());
    }

    public function invalidChildcareEndTimeProvider(): array
    {
        return [
            'equal to endDate time' => ['22:00'],
            'before endDate time'   => ['21:00'],
        ];
    }

    /**
     * @test
     */
    public function it_returns_both_errors_when_both_childcare_times_are_invalid(): void
    {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcareStartTime' => '17:00',
            'childcareEndTime' => '21:00',
        ]);

        $this->assertCount(2, $errors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_start_date_is_absent(): void
    {
        $errors = $this->validator->validate((object) [
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcareStartTime' => '17:00',
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_end_date_is_absent(): void
    {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'childcareEndTime' => '21:00',
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_includes_the_json_pointer_prefix_in_errors(): void
    {
        $errors = $this->validator->validate(
            (object) [
                'startDate' => '2021-05-17T16:00:00+00:00',
                'endDate' => '2021-05-17T22:00:00+00:00',
                'childcareStartTime' => '17:00',
            ],
            '/subEvent/0'
        );

        $this->assertSame('/subEvent/0/childcareStartTime', $errors[0]->getJsonPointer());
    }

    /**
     * @test
     */
    public function it_returns_error_when_childcare_start_time_format_is_invalid(): void
    {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcareStartTime' => 'not-a-time',
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('/childcareStartTime', $errors[0]->getJsonPointer());
    }

    /**
     * @test
     */
    public function it_returns_error_when_childcare_end_time_format_is_invalid(): void
    {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcareEndTime' => 'not-a-time',
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('/childcareEndTime', $errors[0]->getJsonPointer());
    }
}
