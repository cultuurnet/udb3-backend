<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\DBALEventStoreException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportOrganizerRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private MockObject $documentImporter;
    private MockObject $uuidGenerator;
    private ImportOrganizerRequestHandler $importOrganizerRequestHandler;

    protected function setUp(): void
    {
        $this->documentImporter = $this->createMock(DocumentImporterInterface::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);

        $this->importOrganizerRequestHandler = new ImportOrganizerRequestHandler(
            $this->documentImporter,
            $this->uuidGenerator,
            new CallableIriGenerator(fn (string $id) => 'https://mock.uitdatabank.be/organizers/' . $id),
            new CombinedRequestBodyParser()
        );
    }

    /**
     * @test
     */
    public function it_imports_an_organizer_without_id(): void
    {
        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('5829cdfb-21b1-4494-86da-f2dbd7c8d69c');

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Mock organizer',
            ],
            'url' => 'https://www.mock-organizer.be',
        ];

        $expected = [
            '@id' => 'https://mock.uitdatabank.be/organizers/5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Mock organizer',
            ],
            'url' => 'https://www.mock-organizer.be',
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', '5829cdfb-21b1-4494-86da-f2dbd7c8d69c')
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->documentImporter->expects($this->once())
            ->method('import')
            ->with(new DecodedDocument('5829cdfb-21b1-4494-86da-f2dbd7c8d69c', $expected))
            ->willReturn('0a50d1c9-b62f-4f91-bce3-79075d4e778d');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => '5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
                'commandId' => '0a50d1c9-b62f-4f91-bce3-79075d4e778d',
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_imports_an_organizer_with_an_existing_id(): void
    {
        $id = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $given = [
            '@id' => 'incorrect',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Mock organizer',
            ],
            'url' => 'https://www.mock-organizer.be',
        ];

        $expected = [
            '@id' => 'https://mock.uitdatabank.be/organizers/5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Mock organizer',
            ],
            'url' => 'https://www.mock-organizer.be',
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $id)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->documentImporter->expects($this->once())
            ->method('import')
            ->with(new DecodedDocument($id, $expected))
            ->willReturn('0a50d1c9-b62f-4f91-bce3-79075d4e778d');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $id,
                'commandId' => '0a50d1c9-b62f-4f91-bce3-79075d4e778d',
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_an_existing_uuid_of_an_event_or_place_is_given(): void
    {
        $id = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $given = [
            '@id' => 'incorrect',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Mock organizer',
            ],
            'url' => 'https://www.mock-organizer.be',
        ];

        $expected = [
            '@id' => 'https://mock.uitdatabank.be/organizers/5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Mock organizer',
            ],
            'url' => 'https://www.mock-organizer.be',
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $id)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->documentImporter->expects($this->once())
            ->method('import')
            ->with(new DecodedDocument($id, $expected))
            ->willThrowException(
                DBALEventStoreException::create(
                    $this->createMock(UniqueConstraintViolationException::class)
                )
            );

        $this->assertCallableThrowsApiProblem(
            ApiProblem::resourceIdAlreadyInUse('5829cdfb-21b1-4494-86da-f2dbd7c8d69c'),
            fn () => $this->importOrganizerRequestHandler->handle($request)
        );
    }
}
