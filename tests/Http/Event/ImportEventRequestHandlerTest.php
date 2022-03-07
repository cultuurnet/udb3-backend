<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\DBALEventStoreException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ImportEventRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private MockObject $documentImporter;

    private MockObject $uuidGenerator;

    private ImportEventRequestHandler $importEventRequestHandler;

    protected function setUp(): void
    {
        $this->documentImporter = $this->createMock(DocumentImporterInterface::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);

        $this->importEventRequestHandler = new ImportEventRequestHandler(
            $this->documentImporter,
            $this->uuidGenerator,
            new CallableIriGenerator(fn (string $eventId) => 'https://io.uitdatabank.dev/events/' . $eventId)
        );
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_without_id(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';
        $commandId = '473bcc52-58ad-4677-a1f2-23ff6d421512';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expected = [
            '@id' => 'https://io.uitdatabank.dev/events/' . $eventId,
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                    'label' => 'Eten en drinken',
                    'domain' => 'eventtype',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->documentImporter->expects($this->once())
            ->method('import')
            ->with(new DecodedDocument($eventId, $expected))
            ->willReturn($commandId);

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'commandId' => $commandId,
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_with_id(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';
        $commandId = '473bcc52-58ad-4677-a1f2-23ff6d421512';

        $this->uuidGenerator->expects($this->never())
            ->method('generate');

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expected = [
            '@id' => 'https://io.uitdatabank.dev/events/' . $eventId,
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                    'label' => 'Eten en drinken',
                    'domain' => 'eventtype',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->documentImporter->expects($this->once())
            ->method('import')
            ->with(new DecodedDocument($eventId, $expected))
            ->willReturn($commandId);

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'commandId' => $commandId,
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_throws_when_existing_id_is_used(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->never())
            ->method('generate');

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expected = [
            '@id' => 'https://io.uitdatabank.dev/events/' . $eventId,
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                    'label' => 'Eten en drinken',
                    'domain' => 'eventtype',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->documentImporter->expects($this->once())
            ->method('import')
            ->with(new DecodedDocument($eventId, $expected))
            ->willThrowException(
                DBALEventStoreException::create(
                    $this->createMock(UniqueConstraintViolationException::class)
                )
            );

        $this->assertCallableThrowsApiProblem(
            ApiProblem::resourceIdAlreadyInUse($eventId),
            fn () => $this->importEventRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_if_a_required_property_is_missing(): void
    {
        $event = [
            'foo' => 'bar',
        ];

        $expectedErrors = [
            new SchemaError(
                '/',
                'The required properties (mainLanguage, name, terms, calendarType) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_main_language_has_wrong_value(): void
    {
        $event = [
            'mainLanguage' => 'foo',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/mainLanguage',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_main_language_has_wrong_type(): void
    {
        $event = [
            'mainLanguage' => [
                'nl',
            ],
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/mainLanguage',
                'The data (array) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_name_has_no_entries(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/name',
                'The data (array) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_name_entry_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
                'fr' => '   ',
                'en' => '',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/name/fr',
                'The string should match pattern: \S'
            ),
            new SchemaError(
                '/name/en',
                'Minimum string length is 1, found 0'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_name_has_invalid_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => 123,
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/name',
                'The data (integer) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_has_invalid_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'unknownType',
        ];

        $expectedErrors = [
            new SchemaError(
                '/calendarType',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_openingHours_misses_required_fields(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'periodic',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08:00',
                ],
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'closes' => '16:00',
                ],
                [
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/openingHours/0',
                'The required properties (closes) are missing'
            ),
            new SchemaError(
                '/openingHours/1',
                'The required properties (opens) are missing'
            ),
            new SchemaError(
                '/openingHours/2',
                'The required properties (dayOfWeek) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_openingHours_have_invalid_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'periodic',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08:00',
                ],
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'closes' => '16:00',
                ],
                [
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/openingHours/0',
                'The required properties (closes) are missing'
            ),
            new SchemaError(
                '/openingHours/1',
                'The required properties (opens) are missing'
            ),
            new SchemaError(
                '/openingHours/2',
                'The required properties (dayOfWeek) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_openingHours_are_malformed(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'periodic',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08h00',
                    'closes' => '16h00',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/openingHours/0/opens',
                'The string should match pattern: ^\d?\d:\d\d$'
            ),
            new SchemaError(
                '/openingHours/0/closes',
                'The string should match pattern: ^\d?\d:\d\d$'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_dayOfWeek_is_malformed(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'periodic',
            'openingHours' => [
                [
                    'dayOfWeek' => 'monday',
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/openingHours/0/dayOfWeek',
                'The data (string) must match the type: array'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_dayOfWeek_has_unknown_value(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'periodic',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday', 'wed'],
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/openingHours/0/dayOfWeek/2',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms',
                'Array should have at least 1 items, 0 found'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_is_missing_an_id(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'label' => 'foo',
                    'domain' => 'eventtype',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms/0',
                'The required properties (id) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_id_is_not_a_string(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => 1,
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms/0/id',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_id_is_not_known(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1',
                    'label' => 'foo',
                    'domain' => 'facilities',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms',
                'At least 1 array items must match schema'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_has_more_then_one_event_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '0.5.0.0.0',
                    'domain' => 'eventtype',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms',
                'At most 1 array items must match schema'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_can_not_be_resolved_to_an_event(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '0.14.0.0.0',
                    'label' => 'Monument',
                    'domain' => 'eventtype',
                ],
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms',
                'The term 0.14.0.0.0 does not exist or is not supported'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_labels_and_hiddenLabels_have_wrong_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'labels' => 'foo,bar',
            'hiddenLabels' => 'foo,bar',
        ];

        $expectedErrors = [
            new SchemaError(
                '/labels',
                'The data (string) must match the type: array'
            ),
            new SchemaError(
                '/hiddenLabels',
                'The data (string) must match the type: array'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_labels_and_hiddenLabels_are_not_strings(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'labels' => [
                1,
                true,
                '',
                '   ',
                ' d',
            ],
            'hiddenLabels' => [
                1,
                true,
                '',
                '   ',
                ' d',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/labels/0',
                'The data (integer) must match the type: string'
            ),
            new SchemaError(
                '/labels/1',
                'The data (boolean) must match the type: string'
            ),
            new SchemaError(
                '/labels/2',
                'Minimum string length is 2, found 0'
            ),
            new SchemaError(
                '/hiddenLabels/0',
                'The data (integer) must match the type: string'
            ),
            new SchemaError(
                '/hiddenLabels/1',
                'The data (boolean) must match the type: string'
            ),
            new SchemaError(
                '/hiddenLabels/2',
                'Minimum string length is 2, found 0'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_description_has_no_entries(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'description' => [],
        ];

        $expectedErrors = [
            new SchemaError(
                '/description',
                'The data (array) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_description_is_a_string(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'description' => 'Test description',
        ];

        $expectedErrors = [
            new SchemaError(
                '/description',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_description_is_missing_main_language(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'description' => [
                'en' => 'This is the description',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/description',
                'A value in the mainLanguage (nl) is required.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_status_has_wrong_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'status' => 'should not be a string',
        ];

        $expectedErrors = [
            new SchemaError(
                '/status',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_status_reason_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'status' => [
                'type' => 'Unavailable',
                'reason' => [
                    'nl' => 'We zijn nog steeds gesloten.',
                    'en' => '',
                    'fr' => '   ',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/status/reason/fr',
                'The string should match pattern: \S'
            ),
            new SchemaError(
                '/status/reason/en',
                'Minimum string length is 1, found 0'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_status_has_no_main_language(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'status' => [
                'type' => 'Unavailable',
                'reason' => [
                    'en' => 'We zijn nog steeds gesloten.',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/status/reason',
                'A value in the mainLanguage (nl) is required.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingAvailability_has_wrong_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'bookingAvailability' => 'should not be a string',
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingAvailability',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingAvailability_has_invalid_value(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'bookingAvailability' => [
                'type' => 'invalid value',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingAvailability/type',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_typicalAgeRange_has_wrong_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'typicalAgeRange' => 12,
        ];

        $expectedErrors = [
            new SchemaError(
                '/typicalAgeRange',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_typicalAgeRange_has_wrong_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'typicalAgeRange' => '8 TO 12',
        ];

        $expectedErrors = [
            new SchemaError(
                '/typicalAgeRange',
                'The string should match pattern: \A[\d]*-[\d]*\z'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_workflowStatus_has_unknown_value(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'workflowStatus' => 'unknown value',
        ];

        $expectedErrors = [
            new SchemaError(
                '/workflowStatus',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_availableFrom_has_invalid_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'availableFrom' => '05/03/2018',
        ];

        $expectedErrors = [
            new SchemaError(
                '/availableFrom',
                'The data must match the \'date-time\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_availableTo_has_invalid_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'availableTo' => '05/03/2018',
        ];

        $expectedErrors = [
            new SchemaError(
                '/availableTo',
                'The data must match the \'date-time\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_contactPoint_has_invalid_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'contactPoint' => '02 551 18 70',
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_contactPoint_has_invalid_phone(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                    '   ',
                    '',
                    123,
                ],
                'email' => [],
                'url' => [],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint/phone/1',
                'The string should match pattern: \S'
            ),
            new SchemaError(
                '/contactPoint/phone/2',
                'Minimum string length is 1, found 0'
            ),
            new SchemaError(
                '/contactPoint/phone/3',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_contactPoint_has_invalid_email(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'contactPoint' => [
                'phone' => [],
                'email' => [
                    'info@publiq.be',
                    '   ',
                    '',
                    'publiq.be',
                    123,
                ],
                'url' => [],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint/email/1',
                'The data must match the \'email\' format'
            ),
            new SchemaError(
                '/contactPoint/email/2',
                'The data must match the \'email\' format'
            ),
            new SchemaError(
                '/contactPoint/email/3',
                'The data must match the \'email\' format'
            ),
            new SchemaError(
                '/contactPoint/email/4',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_contactPoint_has_invalid_url(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'contactPoint' => [
                'phone' => [],
                'email' => [],
                'url' => [
                    'https://www.publiq.be',
                    '   ',
                    '',
                    'www.uitdatabank.be',
                    123,
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint/url/1',
                'The data must match the \'uri\' format'
            ),
            new SchemaError(
                '/contactPoint/url/2',
                'The data must match the \'uri\' format'
            ),
            new SchemaError(
                '/contactPoint/url/3',
                'The data must match the \'uri\' format'
            ),
            new SchemaError(
                '/contactPoint/url/4',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_invalid_tariff(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => 'Senioren',
                    'price' => '100',
                    'priceCurrency' => 'USD',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo/1/price',
                'The data (string) must match the type: number'
            ),
            new SchemaError(
                '/priceInfo/1/priceCurrency',
                'The data should match one item from enum'
            ),
            new SchemaError(
                '/priceInfo/1/name',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_tariff_has_no_name(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'priceInfo' => [
                [
                    'category' => 'base',
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'price' => 8,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo/1',
                'The required properties (name) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_empty_name(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                        'fr' => '',
                        'en' => '   ',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Senioren',
                        'fr' => '',
                        'en' => '   ',
                    ],
                    'price' => 8,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo/0/name/fr',
                'Minimum string length is 1, found 0'
            ),
            new SchemaError(
                '/priceInfo/0/name/en',
                'The string should match pattern: \S'
            ),
            new SchemaError(
                '/priceInfo/1/name/fr',
                'Minimum string length is 1, found 0'
            ),
            new SchemaError(
                '/priceInfo/1/name/en',
                'The string should match pattern: \S'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_no_base_tariff(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'priceInfo' => [
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Kinderen',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo',
                'At least 1 array items must match schema'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_more_than_one_base_tariff(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basis',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                    ],
                    'price' => 11,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo',
                'At most 1 array items must match schema'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_no_main_language(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannekoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'en' => 'Basis',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'en' => 'Kids',
                    ],
                    'price' => 11,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo/0/name',
                'A value in the mainLanguage (nl) is required.'
            ),
            new SchemaError(
                '/priceInfo/1/name',
                'A value in the mainLanguage (nl) is required.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    private function assertValidationErrors(array $event, array $expectedErrors): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($event)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$expectedErrors),
            fn () => $this->importEventRequestHandler->handle($request)
        );
    }
}
