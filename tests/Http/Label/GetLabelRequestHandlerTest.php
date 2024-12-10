<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetLabelRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    /**
     * @var ReadRepositoryInterface&MockObject
     */
    private $labelRepository;

    private Entity $label;

    private GetLabelRequestHandler $getLabelRequestHandler;

    protected function setUp(): void
    {
        $this->label = new Entity(
            new Uuid('b88f2756-a1d8-4377-a36a-59662fc02d98'),
            'labelName',
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        );

        $this->labelRepository = $this->createMock(ReadRepositoryInterface::class);

        $this->getLabelRequestHandler = new GetLabelRequestHandler($this->labelRepository);
    }

    /**
     * @test
     */
    public function it_can_handle_a_get_label_request(): void
    {
        $this->labelRepository->expects($this->once())
            ->method('getByUuid')
            ->with(new Uuid('b88f2756-a1d8-4377-a36a-59662fc02d98'))
            ->willReturn($this->label);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('labelId', 'b88f2756-a1d8-4377-a36a-59662fc02d98')
            ->build('GET');

        $response = $this->getLabelRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse($this->label),
            $response
        );
    }

    /**
     * @test
     */
    public function it_can_handle_a_get_label_by_name(): void
    {
        $this->labelRepository
            ->expects($this->never())
            ->method('getByUuid');

        $this->labelRepository->expects($this->once())
            ->method('getByName')
            ->with('labelName')
            ->willReturn($this->label);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('labelId', 'labelName')
            ->build('GET');

        $response = $this->getLabelRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse($this->label),
            $response
        );
    }

    /**
     * @test
     */
    public function it_throws_not_found_for_non_existing_label(): void
    {
        $this->labelRepository->expects($this->once())
            ->method('getByUuid')
            ->with(new Uuid('b88f2756-a1d8-4377-a36a-59662fc02d98'))
            ->willReturn(null);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('labelId', 'b88f2756-a1d8-4377-a36a-59662fc02d98')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::labelNotFound('b88f2756-a1d8-4377-a36a-59662fc02d98'),
            fn () => $this->getLabelRequestHandler->handle($request)
        );
    }
}
