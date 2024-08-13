<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\Productions\Doctrine\ProductionSchemaConfigurator;
use CultuurNet\UDB3\Event\Productions\Doctrine\SimilarEventsSchemaConfigurator;
use CultuurNet\UDB3\Event\Productions\Doctrine\SkippedSimilarEventsSchemaConfigurator;
use CultuurNet\UDB3\Event\Productions\SimilarEventsRepository;
use CultuurNet\UDB3\Event\Productions\Suggestion;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

final class SuggestProductionRequestHandlerTest extends TestCase
{
    use DBALTestConnectionTrait;
    use AssertJsonResponseTrait;

    private SimilarEventsRepository $similarEventsRepository;

    private InMemoryDocumentRepository $eventRepository;

    private SuggestProductionRequestHandler $suggestProductionRequestHandler;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->similarEventsRepository = new SimilarEventsRepository($this->getConnection());

        $this->eventRepository = new InMemoryDocumentRepository();

        $this->suggestProductionRequestHandler = new SuggestProductionRequestHandler(
            $this->similarEventsRepository,
            $this->eventRepository
        );
    }

    /**
     * @test
     */
    public function it_returns_a_suggestion(): void
    {
        $suggestion = new Suggestion(
            '3ab86064-045c-42cf-b0c9-24710467031d',
            '04456137-19c4-464b-9c51-272af9f689d8',
            0.75
        );
        $this->similarEventsRepository->add($suggestion);

        $this->eventRepository->save((new JsonDocument(
            '3ab86064-045c-42cf-b0c9-24710467031d',
            Json::encode([
                'id' => '04456137-19c4-464b-9c51-272af9f689d8',
                'name' => 'Event Amsterdam',
            ])
        )));

        $this->eventRepository->save((new JsonDocument(
            '04456137-19c4-464b-9c51-272af9f689d8',
            Json::encode([
                'id' => '04456137-19c4-464b-9c51-272af9f689d8',
                'name' => 'Event Brussel',
            ])
        )));

        $request = (new Psr7RequestBuilder())
            ->build('GET');
        $response = $this->suggestProductionRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'events' => [
                        [
                            'id' => '04456137-19c4-464b-9c51-272af9f689d8',
                            'name' => 'Event Amsterdam',
                        ],
                        [
                            'id' => '04456137-19c4-464b-9c51-272af9f689d8',
                            'name' => 'Event Brussel',
                        ],
                    ],
                    'similarity' => $suggestion->getSimilarity(),
                ]
            ),
            $response
        );
    }
}
