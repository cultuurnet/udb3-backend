<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use PHPUnit\Framework\TestCase;

class UpdateAddressRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateAddressRequestHandler $updateAddressRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateAddressRequestHandler = new UpdateAddressRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_updating_the_address(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'fr')
            ->withBodyFromString(
                '{
                    "streetAddress": "Nieuwstraat 3",
                    "postalCode": "1000",
                    "addressLocality": "Brussel",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $this->updateAddressRequestHandler->handle($updateAddressRequest);

        $this->assertEquals(
            [
                new UpdateAddress(
                    'a088f396-ac96-45c4-b6b2-e2b6afe8af07',
                    new Address(
                        new Street('Nieuwstraat 3'),
                        new PostalCode('1000'),
                        new Locality('Brussel'),
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
    public function it_handles_updating_the_address_without_language_parameter(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withBodyFromString(
                '{
                    "streetAddress": "Nieuwstraat 3",
                    "postalCode": "1000",
                    "addressLocality": "Brussel",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $this->updateAddressRequestHandler->handle($updateAddressRequest);

        $this->assertEquals(
            [
                new UpdateAddress(
                    'a088f396-ac96-45c4-b6b2-e2b6afe8af07',
                    new Address(
                        new Street('Nieuwstraat 3'),
                        new PostalCode('1000'),
                        new Locality('Brussel'),
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
    public function it_requires_a_complete_address(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "street": "Nieuwstraat 3",
                    "postal": "1000",
                    "locality": "Brussel",
                    "country": "BE"
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/',
                    'The required properties (streetAddress, postalCode, addressLocality, addressCountry) are missing'
                )
            ),
            fn () => $this->updateAddressRequestHandler->handle($updateAddressRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_a_country_code_with_max_two_characters(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "Nieuwstraat 3",
                    "postalCode": "1000",
                    "addressLocality": "Brussel",
                    "addressCountry": "BE-nl"
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/addressCountry',
                    'Maximum string length is 2, found 5'
                )
            ),
            fn () => $this->updateAddressRequestHandler->handle($updateAddressRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_a_country_code_with_only_letters(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "Nieuwstraat 3",
                    "postalCode": "1000",
                    "addressLocality": "Brussel",
                    "addressCountry": "12"
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/addressCountry',
                    'The string should match pattern: ^[A-Z]{2}$'
                )
            ),
            fn () => $this->updateAddressRequestHandler->handle($updateAddressRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_a_valid_code_for_language(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'BENL')
            ->withBodyFromString(
                '{
                    "streetAddress": "Nieuwstraat 3",
                    "postalCode": "1000",
                    "addressLocality": "Brussel",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::pathParameterInvalid('The provided language route parameter is not supported.'),
            fn () => $this->updateAddressRequestHandler->handle($updateAddressRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_non_empty_values(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "",
                    "postalCode": "",
                    "addressLocality": "",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/streetAddress',
                    'Minimum string length is 1, found 0'
                ),
                new SchemaError(
                    '/postalCode',
                    'Minimum string length is 1, found 0'
                ),
                new SchemaError(
                    '/addressLocality',
                    'Minimum string length is 1, found 0'
                )
            ),
            fn () => $this->updateAddressRequestHandler->handle($updateAddressRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_non_whitespace_characters(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "      ",
                    "postalCode": "      ",
                    "addressLocality": "      ",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/streetAddress',
                    'The string should match pattern: \S'
                ),
                new SchemaError(
                    '/postalCode',
                    'The string should match pattern: \S'
                ),
                new SchemaError(
                    '/addressLocality',
                    'The string should match pattern: \S'
                )
            ),
            fn () => $this->updateAddressRequestHandler->handle($updateAddressRequest)
        );
    }
}
