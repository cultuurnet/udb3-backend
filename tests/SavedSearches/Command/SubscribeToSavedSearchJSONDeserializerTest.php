<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use PHPUnit\Framework\TestCase;

final class SubscribeToSavedSearchJSONDeserializerTest extends TestCase
{
    private const ID = '3c504b25-b221-4aa5-ad75-5510379ba502';
    private string $userId;

    private SubscribeToSavedSearchJSONDeserializer $deserializer;

    public function setUp(): void
    {
        $this->userId ='xyx';

        $this->deserializer = new SubscribeToSavedSearchJSONDeserializer(
            self::ID,
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
                self::ID,
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
