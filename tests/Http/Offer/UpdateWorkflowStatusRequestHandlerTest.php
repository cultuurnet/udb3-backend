<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\Commands\Moderation\Approve;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish;
use CultuurNet\UDB3\Event\Commands\Moderation\Reject;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\StringLiteral;
use \Iterator;
use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use PHPUnit\Framework\TestCase;

final class UpdateWorkflowStatusRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;
    private UpdateWorkflowStatusRequestHandler $updateWorkflowStatusRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();
        $this->updateWorkflowStatusRequestHandler = new UpdateWorkflowStatusRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_dispatches_no_command_for_workflow_status_draft(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withJsonBodyFromArray(['workflowStatus' => 'DRAFT'])
            ->build('PUT');

        $this->updateWorkflowStatusRequestHandler->handle($request);

        $commands = $this->commandBus->getRecordedCommands();
        $this->assertEmpty($commands);
    }

    /**
     * @test
     */
    public function it_dispatches_a_publish_command_for_workflow_status_ready_for_validation(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withJsonBodyFromArray(['workflowStatus' => 'READY_FOR_VALIDATION'])
            ->build('PUT');

        $this->updateWorkflowStatusRequestHandler->handle($request);

        $commands = $this->commandBus->getRecordedCommands();
        $this->assertCount(1, $commands);

        // Note that we cannot assert the complete Publish command because the publicationDate (set to now) is always
        // slightly different from the one that we would generate.
        $command = $commands[0];
        $this->assertInstanceOf(Publish::class, $command);
        $this->assertEquals('d1422721-f226-48fd-a26d-cb21599ee533', $command->getItemId());
    }

    /**
     * @test
     */
    public function it_dispatches_a_publish_command_for_workflow_status_ready_for_validation_with_an_available_from(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withJsonBodyFromArray(
                [
                    'workflowStatus' => 'READY_FOR_VALIDATION',
                    'availableFrom' => '2222-03-04T12:32:10+01:00',
                ]
            )
            ->build('PUT');

        $this->updateWorkflowStatusRequestHandler->handle($request);

        $commands = $this->commandBus->getRecordedCommands();
        $this->assertCount(1, $commands);

        // Note that we cannot assert the complete Publish command because the publicationDates will have slightly
        // different milli/microseconds in the internal state of DateTimeImmutable.
        // (But they will be dropped when persisted / encoded as JSON)
        // Also note that the publication date MUST be in the future, otherwise the current date will be used. That is
        // why we use a date in 2222 to avoid the test failing at some point in the foreseeable future.
        $command = $commands[0];
        $this->assertInstanceOf(Publish::class, $command);
        $this->assertEquals('d1422721-f226-48fd-a26d-cb21599ee533', $command->getItemId());
        $this->assertEquals('2222-03-04T12:32:10+01:00', $command->getPublicationDate()->format(DATE_RFC3339));
    }

    /**
     * @test
     */
    public function it_dispatches_an_approve_command_for_workflow_status_approved(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withJsonBodyFromArray(['workflowStatus' => 'APPROVED'])
            ->build('PUT');

        $this->updateWorkflowStatusRequestHandler->handle($request);

        $expected = new Approve('d1422721-f226-48fd-a26d-cb21599ee533');

        $commands = $this->commandBus->getRecordedCommands();
        $this->assertEquals([$expected], $commands);
    }

    /**
     * @test
     */
    public function it_dispatches_a_reject_command_for_workflow_status_rejected(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withJsonBodyFromArray(
                [
                    'workflowStatus' => 'REJECTED',
                    'reason' => 'Events that focus primarily on religion are not allowed in UiTdatabank.',
                ]
            )
            ->build('PUT');

        $this->updateWorkflowStatusRequestHandler->handle($request);

        $expected = new Reject(
            'd1422721-f226-48fd-a26d-cb21599ee533',
            new StringLiteral('Events that focus primarily on religion are not allowed in UiTdatabank.')
        );

        $commands = $this->commandBus->getRecordedCommands();
        $this->assertEquals([$expected], $commands);
    }

    /**
     * @test
     */
    public function it_dispatches_a_delete_command_for_workflow_status_deleted(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withJsonBodyFromArray(['workflowStatus' => 'DELETED'])
            ->build('PUT');

        $this->updateWorkflowStatusRequestHandler->handle($request);

        $expected = new DeleteOffer('d1422721-f226-48fd-a26d-cb21599ee533');

        $commands = $this->commandBus->getRecordedCommands();
        $this->assertEquals([$expected], $commands);
    }

    /**
     * @test
     */
    public function it_throws_on_empty_body(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->updateWorkflowStatusRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_json_syntax_in_body(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withBodyFromString('{}}')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidSyntax('JSON'),
            fn () => $this->updateWorkflowStatusRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider invalidBodyDataProvider
     */
    public function it_throws_on_invalid_body_data($bodyData, SchemaError ...$expectedSchemaErrors): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withBodyFromString(Json::encode($bodyData))
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$expectedSchemaErrors),
            fn () => $this->updateWorkflowStatusRequestHandler->handle($request)
        );
    }

    public function invalidBodyDataProvider(): Iterator
    {
        yield 'string_instead_of_object' => [
            'READY_FOR_VALIDATION',
            new SchemaError('/', 'Root element must be an array or object')
        ];

        yield 'array_instead_of_object' => [
            [],
            new SchemaError('/', 'The data (array) must match the type: object')
        ];

        yield 'missing_workflowStatus_property' => [
            (object) [],
            new SchemaError('/', 'The required properties (workflowStatus) are missing')
        ];

        yield 'invalid_workflowStatus_value' => [
            (object) ['workflowStatus' => 'NOT_VALID'],
            new SchemaError('/workflowStatus', 'The data should match one item from enum')
        ];

        yield 'missing_reason_for_workflowStatus_rejected' => [
            (object) ['workflowStatus' => 'REJECTED'],
            new SchemaError('/', 'The required properties (reason) are missing')
        ];

        yield 'invalid_availableFrom' => [
            (object) ['workflowStatus' => 'READY_FOR_VALIDATION', 'availableFrom' => 'invalid'],
            new SchemaError('/availableFrom', 'The data must match the \'date-time\' format')
        ];
    }
}
