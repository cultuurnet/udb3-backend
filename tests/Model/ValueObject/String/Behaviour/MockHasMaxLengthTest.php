<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use PHPUnit\Framework\TestCase;

class MockHasMaxLengthTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testMaxLengthValidation(string $value, int $maxLength, bool $expectException): void
    {
        if ($expectException) {
            $this->expectException(ApiProblem::class);
        }

        $mock = new MockHasMaxLength($value, $maxLength);

        // Check for the happy path
        $this->assertTrue($mock->isSuccess());
    }

    public function dataProvider(): array
    {
        return [
            'Length more than maxLength' => ['ThisIsMoreThanMaxLength', 10, true],
            'Length less than maxLength' => ['Short', 10, false],
            'Length equal to maxLength' => ['ExactlyTen', 10, false],
        ];
    }
}
