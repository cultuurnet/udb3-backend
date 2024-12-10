<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateImage;
use PHPUnit\Framework\TestCase;

final class UpdateImagesRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateImagesRequestHandler $updateImagesRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateImagesRequestHandler = new UpdateImagesRequestHandler($this->commandBus);

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_updating_an_image(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                [
                    'id' => '03789a2f-5063-4062-b7cb-95a0a2280d92',
                    'language' => 'en',
                    'description' => 'A nice image',
                    'copyrightHolder' => 'publiq',
                ],
            ])
            ->build('PATCH');

        $expectedCommand = (new UpdateImage(
            'c269632a-a887-4f21-8455-1631c31e4df5',
            new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92')
        ))
            ->withLanguage(new Language('en'))
            ->withDescription(new Description('A nice image'))
            ->withCopyrightHolder(new CopyrightHolder('publiq'));

        $response = $this->updateImagesRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_handles_updating_multiple_images(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                [
                    'id' => '03789a2f-5063-4062-b7cb-95a0a2280d92',
                    'language' => 'en',
                    'description' => 'A nice image',
                    'copyrightHolder' => 'publiq',
                ],
                [
                    'id' => '5451db35-601e-4d89-8169-72c0aee35543',
                    'language' => 'fr',
                ],
            ])
            ->build('PATCH');

        $expectedCommands = [
            (new UpdateImage(
                'c269632a-a887-4f21-8455-1631c31e4df5',
                new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92')
            ))
                ->withLanguage(new Language('en'))
                ->withDescription(new Description('A nice image'))
                ->withCopyrightHolder(new CopyrightHolder('publiq')),
            (new UpdateImage(
                'c269632a-a887-4f21-8455-1631c31e4df5',
                new Uuid('5451db35-601e-4d89-8169-72c0aee35543')
            ))
                ->withLanguage(new Language('fr')),
        ];

        $response = $this->updateImagesRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals($expectedCommands, $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     * @dataProvider invalidBodyDataProvider
     */
    public function it_throws_an_api_problem_for_an_invalid_body(string $body, ApiProblem $expectedApiProblem): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withBodyFromString($body)
            ->build('PATCH');

        $this->assertCallableThrowsApiProblem(
            $expectedApiProblem,
            fn () => $this->updateImagesRequestHandler->handle($request)
        );
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            [
                '',
                ApiProblem::bodyMissing(),
            ],
            [
                '{{}',
                ApiProblem::bodyInvalidSyntax('JSON'),
            ],
            [
                '{}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The data (object) must match the type: array')
                ),
            ],
            [
                '[]',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'Array should have at least 1 items, 0 found')
                ),
            ],
            [
                '[{
                    "id":"08805a3c-ffe0-4c94-a1bc-453a6dd9d01f"
                }]',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/0', 'Object must have at least 2 properties, 1 found')
                ),
            ],
            [
                '[{
                    "id":"not a uuid",
                    "language":"en"
                }]',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/0/id', 'The data must match the \'uuid\' format')
                ),
            ],
            [
                '[{
                    "id":"08805a3c-ffe0-4c94-a1bc-453a6dd9d01f",
                    "language":"sw",
                    "description":"A nice image",
                    "copyrightHolder":"publiq"
                }]',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/0/language', 'The data should match one item from enum')
                ),
            ],
            [
                '[{
                    "id":"08805a3c-ffe0-4c94-a1bc-453a6dd9d01f",
                    "language":"en",
                    "description":"",
                    "copyrightHolder":"publiq"
                }]',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/0/description', 'Minimum string length is 1, found 0')
                ),
            ],
            [
                '[{
                    "id":"08805a3c-ffe0-4c94-a1bc-453a6dd9d01f",
                    "language":"en",
                    "description":"A nice image",
                    "copyrightHolder":""
                }]',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/0/copyrightHolder', 'Minimum string length is 3, found 0')
                ),
            ],
            [
                '[{
                    "id":"08805a3c-ffe0-4c94-a1bc-453a6dd9d01f",
                    "language":"en",
                    "description":"        ",
                    "copyrightHolder":"      "
                }]',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/0/copyrightHolder', 'The string should match pattern: \S'),
                    new SchemaError('/0/description', 'The string should match pattern: \S')
                ),
            ],
        ];
    }
}
