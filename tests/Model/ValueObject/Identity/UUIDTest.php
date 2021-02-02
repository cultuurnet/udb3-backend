<?php

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use PHPUnit\Framework\TestCase;

class UUIDTest extends TestCase
{
    /**
     * @test
     * @dataProvider validUUIDDataProvider
     * @param string $uuidString
     */
    public function it_should_accept_a_valid_uuid_string($uuidString)
    {
        $uuid = new UUID($uuidString);
        $this->assertEquals($uuidString, $uuid->toString());
    }

    /**
     * @return array
     */
    public function validUUIDDataProvider()
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
     * @param mixed $invalidUuid
     */
    public function it_should_throw_an_exception_if_an_invalid_uuid_is_given($invalidUuid)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("{$invalidUuid} is not a valid uuid.");

        new UUID($invalidUuid);
    }

    /**
     * @return array
     */
    public function invalidUUIDDataProvider()
    {
        return [
            'multi-line' => ['00000000-0000-0000-0000-000000000000' . PHP_EOL],
            'multi-value' => ['76831861-4706-4362-a42d-8710e32bd1ba' . PHP_EOL .
                '74738ff5-5367-5958-9aee-98fffdcd1876'],
            'without-separators' => ['74738ff5536759589aee98fffdcd1876'],
        ];
    }
}
