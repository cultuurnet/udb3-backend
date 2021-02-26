<?php

namespace CultuurNet\UDB3\Deserializer;

use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class JSONDeserializerTest extends TestCase
{
    /**
     * @var JSONDeserializer
     */
    protected $deserializer;

    /**
     * @var JSONDeserializer
     */
    private $assocDeserializer;

    public function setUp()
    {
        $this->deserializer = new JSONDeserializer();

        $this->assocDeserializer = new JSONDeserializer(true);
    }

    /**
     * @test
     */
    public function itThrowsANotWellFormedExceptionForInvalidJson()
    {
        $this->expectException(NotWellFormedException::class);
        $this->expectExceptionMessage('Invalid JSON');

        $this->deserializer->deserialize(
            new StringLiteral(
                '{
                  "eventId": "foo"
                '
            )
        );
    }

    /**
     * @test
     */
    public function itCanDeserializeToAnObject()
    {
        $jsonString = new StringLiteral(
            '{"key1":"value1","key2":{"key3":"value3"}}'
        );

        $actualObject = $this->deserializer->deserialize($jsonString);

        $expectedObject = $this->createExpectedObject();

        $this->assertEquals($expectedObject, $actualObject);
    }

    /**
     * @test
     */
    public function itCanDeserializeToAnAssociativeArray()
    {
        $jsonString = new StringLiteral(
            '{"key1":"value1","key2":{"key3":"value3"}}'
        );

        $actualArray = $this->assocDeserializer->deserialize($jsonString);

        $expectedArray = $this->createExpectedArray();

        $this->assertEquals($expectedArray, $actualArray);
    }

    /**
     * @return \stdClass
     */
    private function createExpectedObject()
    {
        $expectedObject = new \stdClass();
        $expectedObject->key1 = 'value1';
        $value2 = new \stdClass();
        $value2->key3 = 'value3';
        $expectedObject->key2 = $value2;

        return $expectedObject;
    }

    /**
     * @return array
     */
    private function createExpectedArray()
    {
        $expectedArray = [];
        $expectedArray['key1'] = 'value1';
        $value2 = ['key3' => 'value3'];
        $expectedArray['key2'] = $value2;

        return $expectedArray;
    }
}
