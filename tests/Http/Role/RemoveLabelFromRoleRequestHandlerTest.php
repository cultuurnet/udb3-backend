<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\Commands\RemoveLabel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RemoveLabelFromRoleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private RemoveLabelFromRoleRequestHandler $handler;

    private TraceableCommandBus $commandBus;

    /**
     * @var ReadRepositoryInterface&MockObject
     */
    private $labelRepository;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->labelRepository = $this->createMock(ReadRepositoryInterface::class);

        $this->handler = new RemoveLabelFromRoleRequestHandler($this->commandBus, $this->labelRepository);
    }

    /**
     * @test
     */
    public function it_throws_when_role_id_is_not_a_uuid(): void
    {
        $roleId = 'not-a-uuid';
        $labelId = '88b184c2-7dde-4749-bcf4-d2782daee044';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withRouteParameter('labelId', $labelId)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::roleNotFound($roleId),
            fn () => $this->handler->handle($request)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_removes_a_label_when_the_provided_label_identifier_is_a_uuid(): void
    {
        $roleId = '03f982ac-76d6-4fea-9e70-c22c3c05edfc';
        $labelId = '88b184c2-7dde-4749-bcf4-d2782daee044';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withRouteParameter('labelId', $labelId)
            ->build('PUT');

        $actualResponse = $this->handler->handle($request);

        $expectedResponse = new NoContentResponse();
        $expectedCommand = new RemoveLabel(
            new Uuid($roleId),
            new Uuid($labelId)
        );

        $this->assertJsonResponse($expectedResponse, $actualResponse);

        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_throws_when_no_label_with_a_given_name_identifier_can_be_found(): void
    {
        $roleId = '03f982ac-76d6-4fea-9e70-c22c3c05edfc';
        $labelName = 'my-label';

        $this->givenLabelDoesNotExist($labelName);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withRouteParameter('labelId', $labelName)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('There is no label with identifier: ' . $labelName),
            fn () => $this->handler->handle($request)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_removes_label_to_a_role_with_a_given_name_identifier(): void
    {
        $roleId = '03f982ac-76d6-4fea-9e70-c22c3c05edfc';
        $labelId = new Uuid('94367f36-6fce-4ad1-920f-5ab0d2f908d5');
        $labelName = 'my-label';

        $this->givenLabelExists($labelId, $labelName);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withRouteParameter('labelId', $labelName)
            ->build('PUT');

        $actualResponse = $this->handler->handle($request);

        $expectedResponse = new NoContentResponse();
        $expectedCommand = new RemoveLabel(
            new Uuid($roleId),
            $labelId
        );

        $this->assertJsonResponse($expectedResponse, $actualResponse);

        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    private function givenLabelDoesNotExist(string $name): void
    {
        $this->labelRepository->expects($this->once())
            ->method('getByName')
            ->with($name)
            ->willReturn(null);
    }

    private function givenLabelExists(Uuid $labelId, string $name): void
    {
        $label = new Entity(
            $labelId,
            $name,
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->labelRepository->expects($this->once())
            ->method('getByName')
            ->with($name)
            ->willReturn($label);
    }
}
