<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use JsonException;
use PHPUnit\Framework\TestCase;

class JSONDeserializerTest extends TestCase
{
    private JSONDeserializer $deserializer;

    private JSONDeserializer $assocDeserializer;

    public function setUp(): void
    {
        $this->deserializer = new JSONDeserializer();

        $this->assocDeserializer = new JSONDeserializer(true);
    }

    /**
     * @test
     */
    public function itThrowsJsonExceptionForInvalidJson(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        $this->deserializer->deserialize(
            '{
              "eventId": "foo"
            '
        );
    }

    /**
     * @test
     */
    public function itCanDeserializeToAnObject(): void
    {
        $jsonString = '{"key1":"value1","key2":{"key3":"value3"}}';

        $actualObject = $this->deserializer->deserialize($jsonString);

        $expectedObject = $this->createExpectedObject();

        $this->assertEquals($expectedObject, $actualObject);
    }

    /**
     * @test
     */
    public function itCanDeserializeToAnAssociativeArray(): void
    {
        $jsonString = '{"key1":"value1","key2":{"key3":"value3"}}';

        $actualArray = $this->assocDeserializer->deserialize($jsonString);

        $expectedArray = $this->createExpectedArray();

        $this->assertEquals($expectedArray, $actualArray);
    }

    private function createExpectedObject(): \stdClass
    {
        $expectedObject = new \stdClass();
        $expectedObject->key1 = 'value1';
        $value2 = new \stdClass();
        $value2->key3 = 'value3';
        $expectedObject->key2 = $value2;

        return $expectedObject;
    }

    private function createExpectedArray(): array
    {
        $expectedArray = [];
        $expectedArray['key1'] = 'value1';
        $value2 = ['key3' => 'value3'];
        $expectedArray['key2'] = $value2;

        return $expectedArray;
    }
}
