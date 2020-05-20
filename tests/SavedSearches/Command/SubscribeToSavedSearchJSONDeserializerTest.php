<?php

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class SubscribeToSavedSearchJSONDeserializerTest extends TestCase
{
    /**
     * @var SapiVersion
     */
    protected $sapiVersion;

    /**
     * @var StringLiteral
     */
    protected $userId;

    /**
     * @var SubscribeToSavedSearchJSONDeserializer
     */
    protected $deserializer;

    public function setUp()
    {
        $this->sapiVersion = SapiVersion::V2();
        $this->userId = new StringLiteral('xyx');

        $this->deserializer = new SubscribeToSavedSearchJSONDeserializer(
            $this->sapiVersion,
            $this->userId
        );
    }

    /**
     * @test
     */
    public function it_creates_commands_with_the_user_id_passed_in_the_constructor()
    {
        $command = $this->deserializer->deserialize(
            $this->getStringFromFile('subscribe.json')
        );

        $this->assertEquals(
            new SubscribeToSavedSearch(
                $this->sapiVersion,
                $this->userId,
                new StringLiteral('My very first saved search.'),
                new QueryString('city:"Leuven"')
            ),
            $command
        );
    }

    /**
     * @test
     */
    public function it_requires_a_query()
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('query is missing');

        $this->deserializer->deserialize(
            $this->getStringFromFile('subscribe_without_query.json')
        );
    }

    /**
     * @test
     */
    public function it_requires_a_name()
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('name is missing');

        $this->deserializer->deserialize(
            $this->getStringFromFile('subscribe_without_name.json')
        );
    }

    private function getStringFromFile($fileName)
    {
        $json = file_get_contents(
            __DIR__ . '/' . $fileName
        );

        return new StringLiteral($json);
    }
}
