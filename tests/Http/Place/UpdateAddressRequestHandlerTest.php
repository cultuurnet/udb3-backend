<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UpdateAddressRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private const PLACE_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';

    private TraceableCommandBus $commandBus;

    private UpdateAddressRequestHandler $updateAddressRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $placeDocumentRepository = new InMemoryDocumentRepository();

        $placeDocumentRepository->save(
            new JsonDocument(self::PLACE_ID, '{}')
        );

        $this->updateAddressRequestHandler = new UpdateAddressRequestHandler(
            $this->commandBus,
            $placeDocumentRepository
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_updating_the_address(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID)
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "Veldstraat 11",
                    "postalCode": "9000",
                    "addressLocality": "Gent",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $response = $this->updateAddressRequestHandler->handle($updateAddressRequest);

        $this->assertEquals(
            [
                new UpdateAddress(
                    self::PLACE_ID,
                    new Address(
                        new Street('Veldstraat 11'),
                        new PostalCode('9000'),
                        new Locality('Gent'),
                        new CountryCode('BE')
                    ),
                    new Language('nl')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    /**
     * @test
     */
    public function it_handles_changing_the_address(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID)
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "Veldstraat 11",
                    "postalCode": "9000",
                    "addressLocality": "Gent",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $updateAddressRequest2 = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID)
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "Meir 12",
                    "postalCode": "2000",
                    "addressLocality": "Antwerpen",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $this->updateAddressRequestHandler->handle($updateAddressRequest);
        $this->updateAddressRequestHandler->handle($updateAddressRequest2);

        $this->assertEquals(
            [
                new UpdateAddress(
                    self::PLACE_ID,
                    new Address(
                        new Street('Veldstraat 11'),
                        new PostalCode('9000'),
                        new Locality('Gent'),
                        new CountryCode('BE')
                    ),
                    new Language('nl')
                ),
                new UpdateAddress(
                    self::PLACE_ID,
                    new Address(
                        new Street('Meir 12'),
                        new PostalCode('2000'),
                        new Locality('Antwerpen'),
                        new CountryCode('BE')
                    ),
                    new Language('nl')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_supports_multiple_languages(): void
    {
        $updateAddressRequestInDutch = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID)
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "Veldstraat 11",
                    "postalCode": "9000",
                    "addressLocality": "Gent",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $updateAddressRequestInFrench = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID)
            ->withRouteParameter('language', 'fr')
            ->withBodyFromString(
                '{
                    "streetAddress": "Rue du champ 11",
                    "postalCode": "9000",
                    "addressLocality": "Gand",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $this->updateAddressRequestHandler->handle($updateAddressRequestInDutch);
        $this->updateAddressRequestHandler->handle($updateAddressRequestInFrench);

        $this->assertEquals(
            [
                new UpdateAddress(
                    self::PLACE_ID,
                    new Address(
                        new Street('Veldstraat 11'),
                        new PostalCode('9000'),
                        new Locality('Gent'),
                        new CountryCode('BE')
                    ),
                    new Language('nl')
                ),
                new UpdateAddress(
                    self::PLACE_ID,
                    new Address(
                        new Street('Rue du champ 11'),
                        new PostalCode('9000'),
                        new Locality('Gand'),
                        new CountryCode('BE')
                    ),
                    new Language('fr')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_country(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID)
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "Veldstraat 11",
                    "postalCode": "9000",
                    "addressLocality": "Gent",
                    "addressCountry": "BEL"
                }'
            )
            ->build('PUT');

        $this->expectException(InvalidArgumentException::class);

        $this->updateAddressRequestHandler->handle($updateAddressRequest);
    }

    public function it_throws_on_empty_values(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID)
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "",
                    "postalCode": "9000",
                    "addressLocality": "Gent",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $this->expectException(InvalidArgumentException::class);

        $this->updateAddressRequestHandler->handle($updateAddressRequest);
    }
}
