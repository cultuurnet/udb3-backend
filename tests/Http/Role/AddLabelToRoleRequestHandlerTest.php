<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Commands\AddLabel;
use CultuurNet\UDB3\StringLiteral;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;

class AddLabelToRoleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private AddLabelToRoleRequestHandler $handler;

    private TraceableCommandBus $commandBus;

    /**
     * @var ReadRepositoryInterface | MockObject
     */
    private $labelRepository;

    protected function setUp()
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->labelRepository = $this->createMock(ReadRepositoryInterface::class);

        $this->handler = new AddLabelToRoleRequestHandler($this->commandBus, $this->labelRepository);
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
            ->withRouteParameter('labelIdentifier', $labelId)
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
    public function it_adds_a_label_when_the_provided_label_identifier_is_a_uuid(): void
    {
        $roleId = '03f982ac-76d6-4fea-9e70-c22c3c05edfc';
        $labelId = '88b184c2-7dde-4749-bcf4-d2782daee044';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withRouteParameter('labelIdentifier', $labelId)
            ->build('PUT');

        $actualResponse = $this->handler->handle($request);

        $expectedResponse = new Response(StatusCodeInterface::STATUS_NO_CONTENT);
        $expectedCommand = new AddLabel(
            new UUID($roleId),
            new UUID($labelId)
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
            ->withRouteParameter('labelIdentifier', $labelName)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::blank('There is no label with identifier: ' . $labelName, 404),
            fn () => $this->handler->handle($request)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_adds_label_to_a_role_with_a_given_name_identifier(): void
    {
        $roleId = '03f982ac-76d6-4fea-9e70-c22c3c05edfc';
        $labelId = new UUID('94367f36-6fce-4ad1-920f-5ab0d2f908d5');
        $labelName = 'my-label';

        $this->givenLabelExists($labelId, $labelName);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withRouteParameter('labelIdentifier', $labelName)
            ->build('PUT');

        $actualResponse = $this->handler->handle($request);

        $expectedResponse = new Response(StatusCodeInterface::STATUS_NO_CONTENT);
        $expectedCommand = new AddLabel(
            new UUID($roleId),
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

    private function givenLabelExists(UUID $labelId, string $name): void
    {
        $label = new Entity(
            $labelId,
            new StringLiteral($name),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->labelRepository->expects($this->once())
            ->method('getByName')
            ->with($name)
            ->willReturn($label);
    }
}
