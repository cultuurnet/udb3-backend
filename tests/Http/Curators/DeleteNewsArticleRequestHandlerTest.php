<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Http\Curators\DeleteNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteNewsArticleRequestHandlerTest extends TestCase
{
    /** @var NewsArticleRepository&MockObject  */
    private $newsArticleRepository;

    private DeleteNewsArticleRequestHandler $deleteNewsArticleRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->newsArticleRepository = $this->createMock(NewsArticleRepository::class);
        $this->deleteNewsArticleRequestHandler = new DeleteNewsArticleRequestHandler(
            $this->newsArticleRepository
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_handles_deleting_a_news_article(): void
    {
        $articleId = new Uuid('ec00bcd0-41e9-47a0-8364-71aad7e537c5');

        $deleteNewsArticleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', $articleId->toString())
            ->build('GET');

        $this->newsArticleRepository->expects($this->once())
            ->method('delete')
            ->with($articleId);

        $this->deleteNewsArticleRequestHandler->handle($deleteNewsArticleRequest);
    }
}
