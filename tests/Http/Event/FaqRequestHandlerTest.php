<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateFaqs;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Faq\Answer;
use CultuurNet\UDB3\Model\ValueObject\Faq\Faq;
use CultuurNet\UDB3\Model\ValueObject\Faq\FaqItems;
use CultuurNet\UDB3\Model\ValueObject\Faq\Question;
use CultuurNet\UDB3\Model\ValueObject\Faq\TranslatedFaq;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

final class FaqRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const EVENT_ID = '609a8214-51c9-48c0-903f-840a4f38852f';

    private TraceableCommandBus $commandBus;

    private FaqsRequestHandler $faqRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->faqRequestHandler = new FaqsRequestHandler($this->commandBus);
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_dispatches_update_faqs_with_all_incoming_items(): void
    {
        $faqItemId = 'b4575c68-dc04-4b67-9568-63e5d00d4dde';

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
                new UpdateFaqs(
                    self::EVENT_ID,
                    (new FaqItems())->with(
                        (new TranslatedFaq(
                            new Language('nl'),
                            new Faq($faqItemId, new Question('Hoe geraak ik er?'), new Answer('Met de bus.'))
                        ))->withTranslation(
                            new Language('en'),
                            new Faq($faqItemId, new Question('How do I get there?'), new Answer('By bus.'))
                        )
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_dispatches_update_faqs_with_an_empty_list(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([])
            ->build('PUT');

        $response = $this->faqRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(
            [new UpdateFaqs(self::EVENT_ID, new FaqItems())],
            $this->commandBus->getRecordedCommands()
        );
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
