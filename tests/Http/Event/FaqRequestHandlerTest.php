<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\CreateFaqItem;
use CultuurNet\UDB3\Event\Commands\DeleteFaqItem;
use CultuurNet\UDB3\Event\Commands\UpdateFaqItem;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Faq\Answer;
use CultuurNet\UDB3\Model\ValueObject\Faq\FaqItem;
use CultuurNet\UDB3\Model\ValueObject\Faq\Question;
use CultuurNet\UDB3\Model\ValueObject\Faq\TranslatedFaqItem;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FaqRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const EVENT_ID = '609a8214-51c9-48c0-903f-840a4f38852f';

    private DocumentRepository&MockObject $eventDocumentRepository;

    private TraceableCommandBus $commandBus;

    private FaqRequestHandler $faqRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->eventDocumentRepository = $this->createMock(DocumentRepository::class);
        $this->faqRequestHandler = new FaqRequestHandler($this->commandBus, $this->eventDocumentRepository);
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_creates_faq_items_when_there_were_none_before(): void
    {
        $faqItemId = 'b4575c68-dc04-4b67-9568-63e5d00d4dde';

        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with(self::EVENT_ID)
            ->willReturn(
                new JsonDocument(
                    self::EVENT_ID,
                    Json::encode([])
                )
            );

        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([
                [
                    'id' => $faqItemId,
                    'nl' => [
                        'question' => 'Hoe geraak ik er?',
                        'answer' => 'Met de bus.',
                    ],
                    'en' => [
                        'question' => 'How do I get there?',
                        'answer' => 'By bus.',
                    ],
                ],
            ])
            ->build('PUT');

        $response = $this->faqRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [
                new CreateFaqItem(
                    self::EVENT_ID,
                    (new TranslatedFaqItem(
                        new Language('nl'),
                        new FaqItem($faqItemId, new Question('Hoe geraak ik er?'), new Answer('Met de bus.'))
                    ))->withTranslation(
                        new Language('en'),
                        new FaqItem($faqItemId, new Question('How do I get there?'), new Answer('By bus.'))
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_creates_faq_items_when_dutch_is_missing_while_other_language_are_present(): void
    {
        $faqItemId = 'b4575c68-dc04-4b67-9568-63e5d00d4dde';

        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with(self::EVENT_ID)
            ->willReturn(
                new JsonDocument(
                    self::EVENT_ID,
                    Json::encode([])
                )
            );

        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([
                [
                    'id' => $faqItemId,
                    'de' => [
                        'question' => 'Wie komme ich dorthin?',
                        'answer' => 'Mit dem Bus.',
                    ],
                    'en' => [
                        'question' => 'How do I get there?',
                        'answer' => 'By bus.',
                    ],
                ],
            ])
            ->build('PUT');

        $response = $this->faqRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [
                new CreateFaqItem(
                    self::EVENT_ID,
                    (new TranslatedFaqItem(
                        new Language('de'),
                        new FaqItem($faqItemId, new Question('Wie komme ich dorthin?'), new Answer('Mit dem Bus.'))
                    ))->withTranslation(
                        new Language('en'),
                        new FaqItem($faqItemId, new Question('How do I get there?'), new Answer('By bus.'))
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_updates_a_faq_item_when_it_already_exists(): void
    {
        $faqItemId = 'b4575c68-dc04-4b67-9568-63e5d00d4dde';

        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with(self::EVENT_ID)
            ->willReturn(new JsonDocument(
                self::EVENT_ID,
                Json::encode(['faq' => [['id' => $faqItemId, 'nl' => ['question' => 'Hoe geraak ik er?', 'answer' => 'Met de bus.']]]])
            ));

        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([
                [
                    'id' => $faqItemId,
                    'nl' => [
                        'question' => 'Hoe geraak ik er?',
                        'answer' => 'Met de trein.',
                    ],
                ],
            ])
            ->build('PUT');

        $response = $this->faqRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [
                new UpdateFaqItem(
                    self::EVENT_ID,
                    new TranslatedFaqItem(
                        new Language('nl'),
                        new FaqItem($faqItemId, new Question('Hoe geraak ik er?'), new Answer('Met de trein.'))
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_deletes_a_faq_item_when_it_is_not_in_the_incoming_list(): void
    {
        $existingId = 'b4575c68-dc04-4b67-9568-63e5d00d4dde';

        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with(self::EVENT_ID)
            ->willReturn(new JsonDocument(
                self::EVENT_ID,
                Json::encode(['faq' => [['id' => $existingId, 'nl' => ['question' => 'Hoe geraak ik er?', 'answer' => 'Met de bus.']]]])
            ));

        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([])
            ->build('PUT');

        $response = $this->faqRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [new DeleteFaqItem(self::EVENT_ID, $existingId)],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_dispatches_mixed_create_update_and_delete_commands(): void
    {
        $existingId1 = 'aaaaaaaa-0000-0000-0000-000000000001';
        $existingId2 = 'bbbbbbbb-0000-0000-0000-000000000002';
        $newId = 'cccccccc-0000-0000-0000-000000000003';

        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with(self::EVENT_ID)
            ->willReturn(new JsonDocument(
                self::EVENT_ID,
                Json::encode([
                    'faq' => [
                        ['id' => $existingId1, 'nl' => ['question' => 'Vraag 1', 'answer' => 'Antwoord 1']],
                        ['id' => $existingId2, 'nl' => ['question' => 'Vraag 2', 'answer' => 'Antwoord 2']],
                    ],
                ])
            ));

        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([
                // existingId2 updated
                ['id' => $existingId2, 'nl' => ['question' => 'Vraag 2 bijgewerkt', 'answer' => 'Antwoord 2 bijgewerkt']],
                // new item
                ['id' => $newId, 'nl' => ['question' => 'Nieuwe vraag', 'answer' => 'Nieuw antwoord']],
            ])
            ->build('PUT');

        $response = $this->faqRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [
                new UpdateFaqItem(
                    self::EVENT_ID,
                    new TranslatedFaqItem(
                        new Language('nl'),
                        new FaqItem($existingId2, new Question('Vraag 2 bijgewerkt'), new Answer('Antwoord 2 bijgewerkt'))
                    )
                ),
                new CreateFaqItem(
                    self::EVENT_ID,
                    new TranslatedFaqItem(
                        new Language('nl'),
                        new FaqItem($newId, new Question('Nieuwe vraag'), new Answer('Nieuw antwoord'))
                    )
                ),
                new DeleteFaqItem(self::EVENT_ID, $existingId1),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_handles_an_empty_faq_list_with_no_existing_items(): void
    {
        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with(self::EVENT_ID)
            ->willReturn(
                new JsonDocument(
                    self::EVENT_ID,
                    Json::encode([])
                )
            );

        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([])
            ->build('PUT');

        $response = $this->faqRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     * @dataProvider invalidBody
     */
    public function it_throws_an_api_problem_for_an_invalid_body(string $body, ApiProblem $expectedApiProblem): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withBodyFromString($body)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            $expectedApiProblem,
            fn () => $this->faqRequestHandler->handle($request)
        );
    }

    public function invalidBody(): array
    {
        return [
            'missing body' => [
                '',
                ApiProblem::bodyMissing(),
            ],
            'invalid syntax' => [
                '{{}',
                ApiProblem::bodyInvalidSyntax('JSON'),
            ],
            'not an array' => [
                '{}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The data (object) must match the type: array')
                ),
            ],
            'missing required language' => [
                '[{}]',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/0', 'The required properties (nl) are missing'),
                    new SchemaError('/0', 'The required properties (fr) are missing'),
                    new SchemaError('/0', 'The required properties (de) are missing'),
                    new SchemaError('/0', 'The required properties (en) are missing'),
                ),
            ],
            'missing answer' => [
                '[{"nl": {"question": "Hoe geraak ik er?"}}]',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/0/nl', 'The required properties (answer) are missing')
                ),
            ],
            'missing question' => [
                '[{"nl": {"answer": "Met de bus!"}}]',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/0/nl', 'The required properties (question) are missing')
                ),
            ],
        ];
    }
}
