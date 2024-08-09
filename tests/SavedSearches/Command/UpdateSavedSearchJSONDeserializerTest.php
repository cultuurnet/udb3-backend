<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\SampleFiles;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use PHPUnit\Framework\TestCase;

final class UpdateSavedSearchJSONDeserializerTest extends TestCase
{
    private string $userId;
    private string $id;

    private UpdateSavedSearchJSONDeserializer $deserializer;

    public function setUp(): void
    {
        $this->userId ='4c04f805-5eb8-4fdf-90c0-5e0bdf5740ae ';
        $this->id = '550e8400-e29b-41d4-a716-446655440000';

        $this->deserializer = new UpdateSavedSearchJSONDeserializer(
            $this->userId,
            $this->id
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
            new UpdateSavedSearch(
                $this->id,
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
        return SampleFiles::read(__DIR__ . '/' . $fileName);
    }
}
