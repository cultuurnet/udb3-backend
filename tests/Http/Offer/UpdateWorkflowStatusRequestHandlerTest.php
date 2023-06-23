<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Event\Commands\Moderation\Approve as ApproveEvent;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish as PublishEvent;
use CultuurNet\UDB3\Event\Commands\Moderation\Reject as RejectEvent;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Place\Commands\Moderation\Approve as ApprovePlace;
use CultuurNet\UDB3\Place\Commands\Moderation\Publish as PublishPlace;
use CultuurNet\UDB3\Place\Commands\Moderation\Reject as RejectPlace;
use CultuurNet\UDB3\StringLiteral;
use Iterator;
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
     * @dataProvider offerTypeDataProvider
     */
    public function it_dispatches_no_command_for_workflow_status_draft(string $offerType): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withJsonBodyFromArray(['workflowStatus' => 'DRAFT'])
            ->build('PUT');

        $this->updateWorkflowStatusRequestHandler->handle($request);

        $commands = $this->commandBus->getRecordedCommands();
        $this->assertEmpty($commands);
    }

    /**
     * @test
     * @dataProvider publishCommandDataProvider
     */
    public function it_dispatches_a_publish_command_for_workflow_status_ready_for_validation(
        string $offerType,
        string $publishCommandClassName
    ): void {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withJsonBodyFromArray(['workflowStatus' => 'READY_FOR_VALIDATION'])
            ->build('PUT');

        $this->updateWorkflowStatusRequestHandler->handle($request);

        $commands = $this->commandBus->getRecordedCommands();
        $this->assertCount(1, $commands);

        // Note that we cannot assert the complete Publish command because the publicationDate (set to now) is always
        // slightly different from the one that we would generate.
        $command = $commands[0];
        $this->assertInstanceOf($publishCommandClassName, $command);
        $this->assertEquals('d1422721-f226-48fd-a26d-cb21599ee533', $command->getItemId());
    }

    /**
     * @test
     * @dataProvider publishCommandDataProvider
     */
    public function it_dispatches_a_publish_command_for_workflow_status_ready_for_validation_with_an_available_from(
        string $offerType,
        string $publishCommandClassName
    ): void {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
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
        $this->assertInstanceOf($publishCommandClassName, $command);
        $this->assertEquals('d1422721-f226-48fd-a26d-cb21599ee533', $command->getItemId());
        $this->assertEquals('2222-03-04T12:32:10+01:00', $command->getPublicationDate()->format(DATE_RFC3339));
    }

    /**
     * @test
     * @dataProvider approveCommandDataProvider
     */
    public function it_dispatches_an_approve_command_for_workflow_status_approved(
        string $offerType,
        string $approveCommandClassName
    ): void {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withJsonBodyFromArray(['workflowStatus' => 'APPROVED'])
            ->build('PUT');

        $this->updateWorkflowStatusRequestHandler->handle($request);

        $expected = new $approveCommandClassName('d1422721-f226-48fd-a26d-cb21599ee533');

        $commands = $this->commandBus->getRecordedCommands();
        $this->assertEquals([$expected], $commands);
    }

    /**
     * @test
     * @dataProvider rejectCommandDataProvider
     */
    public function it_dispatches_a_reject_command_for_workflow_status_rejected(
        string $offerType,
        string $rejectCommandClassName
    ): void {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withJsonBodyFromArray(
                [
                    'workflowStatus' => 'REJECTED',
                    'reason' => 'Offers that focus primarily on religion are not allowed in UiTdatabank.',
                ]
            )
            ->build('PUT');

        $this->updateWorkflowStatusRequestHandler->handle($request);

        $expected = new $rejectCommandClassName(
            'd1422721-f226-48fd-a26d-cb21599ee533',
            new StringLiteral('Offers that focus primarily on religion are not allowed in UiTdatabank.')
        );

        $commands = $this->commandBus->getRecordedCommands();
        $this->assertEquals([$expected], $commands);
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_dispatches_a_delete_command_for_workflow_status_deleted(string $offerType): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
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
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_on_empty_body(string $offerType): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->updateWorkflowStatusRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_on_invalid_json_syntax_in_body(string $offerType): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
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
     * @param array|object $bodyData
     */
    public function it_throws_on_invalid_body_data(string $offerType, $bodyData, SchemaError ...$expectedSchemaErrors): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'd1422721-f226-48fd-a26d-cb21599ee533')
            ->withBodyFromString(Json::encode($bodyData))
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$expectedSchemaErrors),
            fn () => $this->updateWorkflowStatusRequestHandler->handle($request)
        );
    }

    public function offerTypeDataProvider(): array
    {
        return [['events'], ['places']];
    }

    public function publishCommandDataProvider(): Iterator
    {
        yield ['events', PublishEvent::class];
        yield ['places', PublishPlace::class];
    }

    public function approveCommandDataProvider(): Iterator
    {
        yield ['events', ApproveEvent::class];
        yield ['places', ApprovePlace::class];
    }

    public function rejectCommandDataProvider(): Iterator
    {
        yield ['events', RejectEvent::class];
        yield ['places', RejectPlace::class];
    }

    public function invalidBodyDataProvider(): Iterator
    {
        foreach ($this->offerTypeDataProvider() as $data) {
            $offerType = reset($data);

            yield $offerType . '_string_instead_of_object' => [
                $offerType,
                'READY_FOR_VALIDATION',
                new SchemaError('/', 'Root element must be an array or object'),
            ];

            yield $offerType . '_array_instead_of_object' => [
                $offerType,
                [],
                new SchemaError('/', 'The data (array) must match the type: object'),
            ];

            yield $offerType . '_missing_workflowStatus_property' => [
                $offerType,
                (object) [],
                new SchemaError('/', 'The required properties (workflowStatus) are missing'),
            ];

            yield $offerType . '_invalid_workflowStatus_value' => [
                $offerType,
                (object) ['workflowStatus' => 'NOT_VALID'],
                new SchemaError('/workflowStatus', 'The data should match one item from enum'),
            ];

            yield $offerType . '_missing_reason_for_workflowStatus_rejected' => [
                $offerType,
                (object) ['workflowStatus' => 'REJECTED'],
                new SchemaError('/', 'The required properties (reason) are missing'),
            ];

            yield $offerType . '_invalid_availableFrom' => [
                $offerType,
                (object) ['workflowStatus' => 'READY_FOR_VALIDATION', 'availableFrom' => 'invalid'],
                new SchemaError('/availableFrom', 'The data must match the \'date-time\' format'),
            ];
        }
    }
}
