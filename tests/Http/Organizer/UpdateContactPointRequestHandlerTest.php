<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use PHPUnit\Framework\TestCase;

class UpdateContactPointRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateContactPointRequestHandler $updateContactPointRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateContactPointRequestHandler = new UpdateContactPointRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @dataProvider contactPointDataProvider
     * @test
     */
    public function it_handles_updating_a_contact_point(string $body, ContactPoint $contactPoint): void
    {
        $updateUrlRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withBodyFromString($body)
            ->build('PUT');

        $this->updateContactPointRequestHandler->handle($updateUrlRequest);

        $this->assertEquals(
            [
                new UpdateContactPoint(
                    'a088f396-ac96-45c4-b6b2-e2b6afe8af07',
                    $contactPoint
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    public function contactPointDataProvider(): array
    {
        return [
            'update contact point phone' => [
                '{
                    "phone": ["016 10 20 30"]
                }',
                new ContactPoint(
                    ['016 10 20 30'],
                    [],
                    []
                )
            ],
            'update contact point email' => [
                '{
                    "email": ["info@publiq.be"]
                }',
                new ContactPoint(
                    [],
                    ['info@publiq.be'],
                    []
                )
            ],
            'update contact point url' => [
                '{
                    "url": ["https://www.publiq.be"]
                }',
                new ContactPoint(
                    [],
                    [],
                    ['https://www.publiq.be']
                )
            ],
            'update all contact point information' => [
                '{
                    "phone": ["016 10 20 30"],
                    "email": ["info@publiq.be"],
                    "url": ["https://www.publiq.be"]
                }',
                new ContactPoint(
                    ['016 10 20 30'],
                    ['info@publiq.be'],
                    ['https://www.publiq.be']
                )
            ],
            'update multiple contact point information' => [
                '{
                    "phone": ["016 10 20 30", "016 11 22 33"],
                    "email": ["info@publiq.be", "info@cn.be"],
                    "url": ["https://www.publiq.be", "https://www.cn.be"]
                }',
                new ContactPoint(
                    ['016 10 20 30', '016 11 22 33'],
                    ['info@publiq.be', 'info@cn.be'],
                    ['https://www.publiq.be', 'https://www.cn.be']
                )
            ],
        ];
    }

    /**
     * @test
     */
    public function it_handles_legacy_format(): void
    {
        $updateUrlRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withBodyFromString(
                '[
                    {"type":"url", "value":"https://www.publiq.be"},
                    {"type":"phone", "value":"016 10 20 30"}
                ]'
            )
            ->build('PUT');

        $this->updateContactPointRequestHandler->handle($updateUrlRequest);

        $this->assertEquals(
            [
                new UpdateContactPoint(
                    'a088f396-ac96-45c4-b6b2-e2b6afe8af07',
                    new ContactPoint(
                        ['016 10 20 30'],
                        [],
                        ['https://www.publiq.be']
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_requires_a_body(): void
    {
        $updateUrlRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->updateContactPointRequestHandler->handle($updateUrlRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_a_valid_body(): void
    {
        $updateUrlRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withBodyFromString(
                '{
                    "url": ["https://www.publiq.be"],
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidSyntax('JSON'),
            fn () => $this->updateContactPointRequestHandler->handle($updateUrlRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_a_valid_url(): void
    {
        $updateUrlRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withBodyFromString(
                '{
                    "url": ["ftp://www.publiq.be"]
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/url/0', 'The string should match pattern: ^http[s]?:\/\/')
            ),
            fn () => $this->updateContactPointRequestHandler->handle($updateUrlRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_a_valid_email(): void
    {
        $updateUrlRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withBodyFromString(
                '{
                    "email": ["info#publiq.be"]
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/email/0', 'The data must match the \'email\' format')
            ),
            fn () => $this->updateContactPointRequestHandler->handle($updateUrlRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_a_non_empty_phone(): void
    {
        $updateUrlRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withBodyFromString(
                '{
                    "phone": [""]
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/phone/0', 'Minimum string length is 1, found 0')
            ),
            fn () => $this->updateContactPointRequestHandler->handle($updateUrlRequest)
        );
    }
}
