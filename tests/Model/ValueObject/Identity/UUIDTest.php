<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use PHPUnit\Framework\TestCase;

class UUIDTest extends TestCase
{
    /**
     * @test
     * @dataProvider validUUIDDataProvider
     */
    public function it_should_accept_a_valid_uuid_string(string $uuidString): void
    {
        $uuid = new UUID($uuidString);
        $this->assertEquals($uuidString, $uuid->toString());
    }

    public function validUUIDDataProvider(): array
    {
        return [
            'nil' => ['00000000-0000-0000-0000-000000000000'],
            'v4' => ['76831861-4706-4362-a42d-8710e32bd1ba'],
            'v5' => ['74738ff5-5367-5958-9aee-98fffdcd1876'],
            'udb2' => ['74738ff5-5367-5958-9aee98fffdcd1876'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidUUIDDataProvider
     */
    public function it_should_throw_an_exception_if_an_invalid_uuid_is_given(string $invalidUuid): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("{$invalidUuid} is not a valid uuid.");

        new UUID($invalidUuid);
    }


    public function invalidUUIDDataProvider(): array
    {
        return [
            'multi-line' => ['00000000-0000-0000-0000-000000000000' . PHP_EOL],
            'multi-value' => ['76831861-4706-4362-a42d-8710e32bd1ba' . PHP_EOL . '74738ff5-5367-5958-9aee-98fffdcd1876'],
            'without-separators' => ['74738ff5536759589aee98fffdcd1876'],
        ];
    }
}
