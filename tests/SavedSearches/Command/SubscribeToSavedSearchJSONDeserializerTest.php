<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use PHPUnit\Framework\TestCase;

class SubscribeToSavedSearchJSONDeserializerTest extends TestCase
{
    protected string $userId;

    protected SubscribeToSavedSearchJSONDeserializer $deserializer;

    public function setUp(): void
    {
        $this->userId ='xyx';

        $this->deserializer = new SubscribeToSavedSearchJSONDeserializer(
            $this->userId
        );
    }

    /**
     * @test
     */
    public function it_creates_commands_with_the_user_id_passed_in_the_constructor(): void
    {
        $command = $this->deserializer->deserialize(
            $this->getStringFromFile('subscribe.json')
        );

        $this->assertEquals(
            new SubscribeToSavedSearch(
                $this->userId,
                'My very first saved search.',
                new QueryString('city:"Leuven"')
            ),
            $command
        );
    }

    /**
     * @test
     */
    public function it_requires_a_query(): void
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
    public function it_requires_a_name(): void
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('name is missing');

        $this->deserializer->deserialize(
            $this->getStringFromFile('subscribe_without_name.json')
        );
    }

    private function getStringFromFile(string $fileName): string
    {
        return file_get_contents(__DIR__ . '/' . $fileName);
    }
}
