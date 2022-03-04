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
    public function it_throws_an_exception_if_a_required_property_is_missing(): void
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
    public function it_throws_an_exception_if_main_language_has_wrong_value(): void
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
    public function it_throws_an_exception_if_main_language_has_wrong_type(): void
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
    public function it_throws_an_exception_if_name_has_no_entries(): void
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
    public function it_throws_an_exception_if_name_entry_is_empty(): void
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
    public function it_throws_an_exception_if_name_has_invalid_format(): void
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
    public function it_throws_an_exception_if_calendarType_has_invalid_format(): void
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
    public function it_throws_an_exception_if_openingHours_misses_required_fields(): void
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
    public function it_throws_an_exception_if_openingHours_have_invalid_format(): void
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
    public function it_throws_an_exception_if_openingHours_are_malformed(): void
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
