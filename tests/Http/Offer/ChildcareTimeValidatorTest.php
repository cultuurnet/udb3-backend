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
    public function it_returns_no_errors_when_childcare_is_absent(): void
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
    public function it_returns_no_errors_when_childcare_start_is_before_start_date_time(): void
    {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcare' => (object)['start' => '15:00', 'end' => '23:00'],
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_returns_no_errors_when_childcare_end_is_after_end_date_time(): void
    {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcare' => (object)['start' => '15:00', 'end' => '23:00'],
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     * @dataProvider invalidChildcareStartProvider
     */
    public function it_returns_error_when_childcare_start_is_not_before_start_date_time(
        string $childcareStart
    ): void {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcare' => (object)['start' => $childcareStart, 'end' => '23:00'],
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('/childcare/start', $errors[0]->getJsonPointer());
        $this->assertSame('childcare.start must be before the time portion of startDate', $errors[0]->getError());
    }

    public function invalidChildcareStartProvider(): array
    {
        return [
            'equal to startDate time' => ['16:00'],
            'after startDate time'    => ['17:00'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidChildcareEndProvider
     */
    public function it_returns_error_when_childcare_end_is_not_after_end_date_time(
        string $childcareEnd
    ): void {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcare' => (object)['start' => '15:00', 'end' => $childcareEnd],
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('/childcare/end', $errors[0]->getJsonPointer());
        $this->assertSame('childcare.end must be after the time portion of endDate', $errors[0]->getError());
    }

    public function invalidChildcareEndProvider(): array
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
            'childcare' => (object)['start' => '17:00', 'end' => '21:00'],
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
            'childcare' => (object)['start' => '17:00', 'end' => '23:00'],
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
            'childcare' => (object)['start' => '15:00', 'end' => '21:00'],
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
                'childcare' => (object)['start' => '17:00', 'end' => '23:00'],
            ],
            '/subEvent/0'
        );

        $this->assertSame('/subEvent/0/childcare/start', $errors[0]->getJsonPointer());
    }

    /**
     * @test
     */
    public function it_returns_error_when_childcare_start_format_is_invalid(): void
    {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcare' => (object)['start' => 'not-a-time', 'end' => '23:00'],
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('/childcare/start', $errors[0]->getJsonPointer());
    }

    /**
     * @test
     */
    public function it_returns_error_when_childcare_end_format_is_invalid(): void
    {
        $errors = $this->validator->validate((object) [
            'startDate' => '2021-05-17T16:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'childcare' => (object)['start' => '15:00', 'end' => 'not-a-time'],
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('/childcare/end', $errors[0]->getJsonPointer());
    }
}
