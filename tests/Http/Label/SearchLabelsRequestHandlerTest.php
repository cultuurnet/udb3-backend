<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Http\Label\Query\QueryFactory;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\PagedCollectionResponse;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SearchLabelsRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private array $labels = [];

    /**
     * @var ReadRepositoryInterface&MockObject
     */
    private $labelRepository;

    private SearchLabelsRequestHandler $searchLabelsRequestHandler;

    protected function setUp(): void
    {
        $this->labels[] = new Entity(
            new Uuid('b88f2756-a1d8-4377-a36a-59662fc02d98'),
            'Invisible Private Label',
            Visibility::invisible(),
            Privacy::private()
        );

        $this->labels[] = new Entity(
            new Uuid('b88f2756-a1d8-4377-a36a-59662fc02d98'),
            'Visible Public Label',
            Visibility::visible(),
            Privacy::public()
        );

        $this->labelRepository = $this->createMock(ReadRepositoryInterface::class);

        $this->searchLabelsRequestHandler = new SearchLabelsRequestHandler(
            $this->labelRepository,
            new QueryFactory('123')
        );
    }

    /**
     * @test
     */
    public function it_can_search_labels(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('labels?query=label&start=5&limit=2')
            ->build('GET');

        $this->labelRepository->expects($this->once())
            ->method('searchTotalLabels')
            ->with(new Query('label', '123', 5, 2))
            ->willReturn(count($this->labels));

        $this->labelRepository->expects($this->once())
            ->method('search')
            ->with(new Query('label', '123', 5, 2))
            ->willReturn($this->labels);

        $response = $this->searchLabelsRequestHandler->handle($request);

        $this->assertJsonResponse(
            new PagedCollectionResponse(
                2,
                2,
                $this->labels
            ),
            $response,
        );
    }

    /**
     * @test
     */
    public function it_returns_an_empty_collection_when_no_labels_are_found(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('labels?query=label&start=5&limit=2')
            ->build('GET');

        $this->labelRepository->expects($this->once())
            ->method('searchTotalLabels')
            ->with(new Query('label', '123', 5, 2))
            ->willReturn(0);

        $this->labelRepository->expects($this->never())
            ->method('search');

        $response = $this->searchLabelsRequestHandler->handle($request);

        $this->assertJsonResponse(
            new PagedCollectionResponse(
                2,
                0,
                []
            ),
            $response,
        );
    }

    /**
     * @test https://jira.publiq.be/browse/III-4855
     */
    public function it_uses_default_start_and_limit_when_not_provided(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('labels')
            ->build('GET');

        $this->labelRepository->expects($this->once())
            ->method('searchTotalLabels')
            ->with(new Query('', '123', 0, 30))
            ->willReturn(count($this->labels));

        $this->labelRepository->expects($this->once())
            ->method('search')
            ->with(new Query('', '123', 0, 30))
            ->willReturn($this->labels);

        $response = $this->searchLabelsRequestHandler->handle($request);

        $this->assertJsonResponse(
            new PagedCollectionResponse(
                30,
                2,
                $this->labels
            ),
            $response,
        );
    }
}
