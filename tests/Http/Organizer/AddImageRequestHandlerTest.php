<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\Properties\Description as ImageDescription;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Commands\AddImage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AddImageRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private AddImageRequestHandler $addImageRequestHandler;

    private Repository&MockObject $imageRepository;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->imageRepository = $this->createMock(Repository::class);

        $this->addImageRequestHandler = new AddImageRequestHandler($this->commandBus, $this->imageRepository);

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider addImageDataProvider
     */
    public function it_handles_adding_an_image(array $body, Image $image): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray($body)
            ->build('POST');

        $this->imageRepository->method('load')
            ->with('03789a2f-5063-4062-b7cb-95a0a2280d92')
            ->willReturn(
                MediaObject::create(
                    new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92'),
                    MIMEType::fromSubtype('jpeg'),
                    new ImageDescription('Uploaded image'),
                    new CopyrightHolder('madewithlove'),
                    new Url('https://images.uitdatabank.be/03789a2f-5063-4062-b7cb-95a0a2280d92.jpg'),
                    new Language('nl')
                )
            );

        $expectedCommand = new AddImage(
            'c269632a-a887-4f21-8455-1631c31e4df5',
            $image
        );

        $response = $this->addImageRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    public function addImageDataProvider(): array
    {
        return [
            'All properties' => [
                [
                    'id' => '03789a2f-5063-4062-b7cb-95a0a2280d92',
                    'language' => 'en',
                    'description' => 'A nice image',
                    'copyrightHolder' => 'publiq',
                ],
                new Image(
                    new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92'),
                    new Language('en'),
                    new Description('A nice image'),
                    new CopyrightHolder('publiq')
                ),
            ],
            'Only id provided' => [
                [
                    'id' => '03789a2f-5063-4062-b7cb-95a0a2280d92',
                ],
                new Image(
                    new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92'),
                    new Language('nl'),
                    new Description('Uploaded image'),
                    new CopyrightHolder('madewithlove')
                ),
            ],
            'Only id and language provided' => [
                [
                    'id' => '03789a2f-5063-4062-b7cb-95a0a2280d92',
                    'language' => 'fr',
                ],
                new Image(
                    new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92'),
                    new Language('fr'),
                    new Description('Uploaded image'),
                    new CopyrightHolder('madewithlove')
                ),
            ],
            'Only id and description provided' => [
                [
                    'id' => '03789a2f-5063-4062-b7cb-95a0a2280d92',
                    'description' => 'A nice image',
                ],
                new Image(
                    new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92'),
                    new Language('nl'),
                    new Description('A nice image'),
                    new CopyrightHolder('madewithlove')
                ),
            ],
            'Only id and copyrigght holder provided' => [
                [
                    'id' => '03789a2f-5063-4062-b7cb-95a0a2280d92',
                    'copyrightHolder' => 'publiq',
                ],
                new Image(
                    new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92'),
                    new Language('nl'),
                    new Description('Uploaded image'),
                    new CopyrightHolder('publiq')
                ),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_image_not_found(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'id' => '08805a3c-ffe0-4c94-a1bc-453a6dd9d01f',
                'language' => 'en',
                'description' => 'A nice image',
                'copyrightHolder' => 'publiq',
            ])
            ->build('POST');

        $this->imageRepository->method('load')
            ->with('08805a3c-ffe0-4c94-a1bc-453a6dd9d01f')
            ->willThrowException(new AggregateNotFoundException());

        $this->assertCallableThrowsApiProblem(
            ApiProblem::imageNotFound('08805a3c-ffe0-4c94-a1bc-453a6dd9d01f'),
            fn () => $this->addImageRequestHandler->handle($request)
        );
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
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            $expectedApiProblem,
            fn () => $this->addImageRequestHandler->handle($request)
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
                    new SchemaError('/', 'The required properties (id) are missing')
                ),
            ],
            [
                '{
                    "id":"not a uuid",
                    "language":"en",
                    "description":"A nice image",
                    "copyrightHolder":"publiq"
                }',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/id', 'The data must match the \'uuid\' format')
                ),
            ],
            [
                '{
                    "id":"08805a3c-ffe0-4c94-a1bc-453a6dd9d01f",
                    "language":"sw",
                    "description":"A nice image",
                    "copyrightHolder":"publiq"
                }',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/language', 'The data should match one item from enum')
                ),
            ],
            [
                '{
                    "id":"08805a3c-ffe0-4c94-a1bc-453a6dd9d01f",
                    "language":"en",
                    "description":"",
                    "copyrightHolder":"publiq"
                }',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/description', 'Minimum string length is 1, found 0')
                ),
            ],
            [
                '{
                    "id":"08805a3c-ffe0-4c94-a1bc-453a6dd9d01f",
                    "language":"en",
                    "description":"A nice image",
                    "copyrightHolder":"a"
                }',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/copyrightHolder', 'Minimum string length is 2, found 1')
                ),
            ],
            [
                '{
                    "id":"08805a3c-ffe0-4c94-a1bc-453a6dd9d01f",
                    "language":"en",
                    "description":"   ",
                    "copyrightHolder":"       "
                }',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/copyrightHolder', 'The string should match pattern: \S'),
                    new SchemaError('/description', 'The string should match pattern: \S'),
                ),
            ],
        ];
    }
}
