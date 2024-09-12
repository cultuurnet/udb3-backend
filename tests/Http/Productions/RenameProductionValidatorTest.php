<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use PHPUnit\Framework\TestCase;

class RenameProductionValidatorTest extends TestCase
{
    private RenameProductionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RenameProductionValidator();
    }

    /**
     * @test
     */
    public function it_allows_valid_data(): void
    {
        $data = [
            'name' => 'foo',
        ];

        $this->validator->validate($data);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_fails_on_missing_name_property(): void
    {
        $data = [];
        $this->expectException(DataValidationException::class);
        $this->validator->validate($data);
    }

    /**
     * @test
     */
    public function it_fails_on_incorrect_name_type(): void
    {
        $data = ['name' => false];
        $this->expectException(DataValidationException::class);
        $this->validator->validate($data);
    }

    /**
     * @test
     */
    public function it_fails_on_empty_name(): void
    {
        $data = ['name' => ''];
        $this->expectException(DataValidationException::class);
        $this->validator->validate($data);
    }
}
