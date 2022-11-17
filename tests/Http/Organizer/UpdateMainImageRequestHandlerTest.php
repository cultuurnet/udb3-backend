<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\ImageMustBeLinkedException;
use CultuurNet\UDB3\Organizer\Commands\UpdateMainImage;
use PHPUnit\Framework\TestCase;

final class UpdateMainImageRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateMainImageRequestHandler $updateMainImageRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateMainImageRequestHandler = new UpdateMainImageRequestHandler($this->commandBus);

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_updating_the_main_image(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'imageId' => '03789a2f-5063-4062-b7cb-95a0a2280d92',
            ])
            ->build('PUT');

        $expectedCommand = new UpdateMainImage(
            'c269632a-a887-4f21-8455-1631c31e4df5',
            new UUID('03789a2f-5063-4062-b7cb-95a0a2280d92')
        );

        $response = $this->updateMainImageRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_support_media_object_id(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'mediaObjectId' => '03789a2f-5063-4062-b7cb-95a0a2280d92',
            ])
            ->build('PUT');

        $expectedCommand = new UpdateMainImage(
            'c269632a-a887-4f21-8455-1631c31e4df5',
            new UUID('03789a2f-5063-4062-b7cb-95a0a2280d92')
        );

        $response = $this->updateMainImageRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_throws_when_image_is_not_linked_to_offer(): void
    {
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new ImageMustBeLinkedException());

        $handler = new UpdateMainImageRequestHandler($commandBus);

        $imageId = '03789a2f-5063-4062-b7cb-95a0a2280d92';
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'imageId' => $imageId,
            ])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::imageMustBeLinkedToResource($imageId),
            fn () => $handler->handle($request)
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
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            $expectedApiProblem,
            fn () => $this->updateMainImageRequestHandler->handle($request)
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
                    new SchemaError('/', 'The required properties (imageId) are missing')
                ),
            ],
            [
                '{
                    "imageId":"not a uuid"
                }',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/imageId', 'The data must match the \'uuid\' format')
                ),
            ],
        ];
    }
}
