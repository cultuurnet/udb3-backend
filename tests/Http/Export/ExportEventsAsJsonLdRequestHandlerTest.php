<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Export;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsJsonLD;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use PHPUnit\Framework\TestCase;

final class ExportEventsAsJsonLdRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private TraceableCommandBus $commandBus;

    private ExportEventsAsJsonLdRequestHandler $exportEventsAsJsonLdRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->exportEventsAsJsonLdRequestHandler = new ExportEventsAsJsonLdRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_exporting_events_as_json_ld(): void
    {
        $exportEventsAsJsonLdRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray([
                'include' => [
                    'calendarSummary',
                ],
                'query' => 'Dansvoorstellingen',
                'email' => 'jane@anonymous.com',
            ])
            ->build('POST');

        $response = $this->exportEventsAsJsonLdRequestHandler->handle($exportEventsAsJsonLdRequest);

        $this->assertEquals(
            [
                new ExportEventsAsJsonLD(
                    new EventExportQuery('Dansvoorstellingen'),
                    ['calendarSummary'],
                    new EmailAddress('jane@anonymous.com')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new JsonResponse(['commandId' => Uuid::NIL]),
            $response
        );
    }
}
