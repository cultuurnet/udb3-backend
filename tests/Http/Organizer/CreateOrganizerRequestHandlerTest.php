<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateOrganizerRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableEventStore $eventStore;

    private TraceableCommandBus $commandBus;

    /** @var IriGeneratorInterface|MockObject */
    private $iriGenerator;

    private CreateOrganizerRequestHandler $createOrganizerRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->eventStore = new TraceableEventStore(new InMemoryEventStore());
        $this->eventStore->trace();

        $organizerRepository = new OrganizerRepository(
            $this->eventStore,
            new SimpleEventBus()
        );

        $this->commandBus = new TraceableCommandBus();

        /** @var UuidGeneratorInterface|MockObject $uuidGenerator */
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->method('generate')
            ->willReturn('6c583739-a848-41ab-b8a3-8f7dab6f8ee1');

        $this->createOrganizerRequestHandler = new CreateOrganizerRequestHandler(
            $organizerRepository,
            $this->commandBus,
            $uuidGenerator,
            new CallableIriGenerator(fn ($id) => 'https://io.uitdatabank.be/organizers/' . $id)
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    protected function tearDown(): void
    {
        $this->eventStore->clearEvents();
    }

    /**
     * @test
     * @dataProvider legacyOrganizerProvider
     */
    public function it_handles_creating_an_organizer_from_legacy_format(
        array $body,
        array $expectedEvents,
        array $expectedCommands
    ): void {
        $createOrganizerRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray($body)
            ->build('POST');

        $this->createOrganizerRequestHandler->handle($createOrganizerRequest);

        $this->assertEquals($expectedEvents, $this->eventStore->getEvents());

        $this->assertEquals($expectedCommands, $this->commandBus->getRecordedCommands());
    }

    public function legacyOrganizerProvider(): array
    {
        return [
            'organizer with only required properties' => [
                [
                    'mainLanguage' => 'nl',
                    'name' => 'publiq',
                    'website' => 'https://www.publiq.be',
                ],
                [
                    new OrganizerCreatedWithUniqueWebsite(
                        '6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                        'nl',
                        'https://www.publiq.be',
                        'publiq'
                    ),
                ],
                [],
            ],
            'organizer with optional address' => [
                [
                    'mainLanguage' => 'nl',
                    'name' => 'publiq',
                    'website' => 'https://www.publiq.be',
                    'address' => [
                        'streetAddress'=> 'Henegouwenkaai 41-43',
                        'postalCode'=> '1080',
                        'addressLocality'=> 'Brussel',
                        'addressCountry'=> 'BE',
                    ],
                ],
                [
                    new OrganizerCreatedWithUniqueWebsite(
                        '6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                        'nl',
                        'https://www.publiq.be',
                        'publiq'
                    ),
                ],
                [
                    new UpdateAddress(
                        '6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                        new Address(
                            new Street('Henegouwenkaai 41-43'),
                            new PostalCode('1080'),
                            new Locality('Brussel'),
                            new CountryCode('BE')
                        ),
                        new Language('nl')
                    ),
                ],
            ],
            'organizer with optional contact point' => [
                [
                    'mainLanguage' => 'nl',
                    'name' => 'publiq',
                    'website' => 'https://www.publiq.be',
                    'contact' => [
                        [
                            'type'=> 'url',
                            'value'=> 'https://www.publiq.be',
                        ],
                        [
                            'type'=> 'url',
                            'value'=> 'https://www.publiq.com',
                        ],
                        [
                            'type'=> 'phone',
                            'value'=> '016 10 20 30',
                        ],
                        [
                            'type'=> 'email',
                            'value'=> 'info@publiq.be',
                        ],
                        [
                            'type'=> 'email',
                            'value'=> 'info@publiq.com',
                        ],
                    ],
                ],
                [
                    new OrganizerCreatedWithUniqueWebsite(
                        '6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                        'nl',
                        'https://www.publiq.be',
                        'publiq'
                    ),
                ],
                [
                    new UpdateContactPoint(
                        '6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                        new ContactPoint(
                            new TelephoneNumbers(
                                new TelephoneNumber('016 10 20 30')
                            ),
                            new EmailAddresses(
                                new EmailAddress('info@publiq.be'),
                                new EmailAddress('info@publiq.com')
                            ),
                            new Urls(
                                new Url('https://www.publiq.be'),
                                new Url('https://www.publiq.com')
                            )
                        )
                    ),
                ],
            ],
            'organizer with optional address and contact point' => [
                [
                    'mainLanguage' => 'nl',
                    'name' => 'publiq',
                    'website' => 'https://www.publiq.be',
                    'address' => [
                        'streetAddress'=> 'Henegouwenkaai 41-43',
                        'postalCode'=> '1080',
                        'addressLocality'=> 'Brussel',
                        'addressCountry'=> 'BE',
                    ],
                    'contact' => [
                        [
                            'type'=> 'url',
                            'value'=> 'https://www.publiq.be',
                        ],
                        [
                            'type'=> 'url',
                            'value'=> 'https://www.publiq.com',
                        ],
                        [
                            'type'=> 'phone',
                            'value'=> '016 10 20 30',
                        ],
                        [
                            'type'=> 'email',
                            'value'=> 'info@publiq.be',
                        ],
                        [
                            'type'=> 'email',
                            'value'=> 'info@publiq.com',
                        ],
                    ],
                ],
                [
                    new OrganizerCreatedWithUniqueWebsite(
                        '6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                        'nl',
                        'https://www.publiq.be',
                        'publiq'
                    ),
                ],
                [
                    new UpdateContactPoint(
                        '6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                        new ContactPoint(
                            new TelephoneNumbers(
                                new TelephoneNumber('016 10 20 30')
                            ),
                            new EmailAddresses(
                                new EmailAddress('info@publiq.be'),
                                new EmailAddress('info@publiq.com')
                            ),
                            new Urls(
                                new Url('https://www.publiq.be'),
                                new Url('https://www.publiq.com')
                            )
                        )
                    ),
                    new UpdateAddress(
                        '6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                        new Address(
                            new Street('Henegouwenkaai 41-43'),
                            new PostalCode('1080'),
                            new Locality('Brussel'),
                            new CountryCode('BE')
                        ),
                        new Language('nl')
                    ),
                ],
            ],

        ];
    }

    /**
     * @test
     */
    public function it_requires_a_body(): void
    {
        $createOrganizerRequest = $this->psr7RequestBuilder
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->createOrganizerRequestHandler->handle($createOrganizerRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_a_valid_json_syntax_body(): void
    {
        $createOrganizerRequest = $this->psr7RequestBuilder
            ->withBodyFromString('invalid')
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidSyntax('JSON'),
            fn () => $this->createOrganizerRequestHandler->handle($createOrganizerRequest)
        );
    }

    /**
     * @test
     * @dataProvider invalidOrganizerProvider
     */
    public function it_fails_on_invalid_data(array $body, SchemaError $schemaError): void
    {
        $createOrganizerRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray($body)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData($schemaError),
            fn () => $this->createOrganizerRequestHandler->handle($createOrganizerRequest)
        );
    }

    public function invalidOrganizerProvider(): array
    {
        return [
            'missing mainLanguage' => [
                [
                    'name' => 'publiq',
                    'website' => 'https://www.publiq.be',
                ],
                new SchemaError(
                    '/',
                    'The required properties (mainLanguage) are missing'
                ),
            ],
            'missing name' => [
                [
                    'mainLanguage' => 'nl',
                    'website' => 'https://www.publiq.be',
                ],
                new SchemaError(
                    '/',
                    'The required properties (name) are missing'
                ),
            ],
            'missing website' => [
                [
                    'name' => 'publiq',
                    'mainLanguage' => 'nl',
                ],
                new SchemaError(
                    '/',
                    'The required properties (website) are missing'
                ),
            ],
            'missing mainLanguage, name and website' => [
                [
                ],
                new SchemaError(
                    '/',
                    'The required properties (mainLanguage, website, name) are missing'
                ),
            ],
            'invalid address format' => [
                [
                    'mainLanguage' => 'nl',
                    'name' => 'publiq',
                    'website' => 'https://www.publiq.be',
                    'address' => 'invalid',
                ],
                new SchemaError(
                    '/address',
                    'The data (string) must match the type: object'
                ),
            ],
            'address with missing fields' => [
                [
                    'mainLanguage' => 'nl',
                    'name' => 'publiq',
                    'website' => 'https://www.publiq.be',
                    'address' => [
                        'missing' => 'all fields',
                    ],
                ],
                new SchemaError(
                    '/address',
                    'The required properties (streetAddress, postalCode, addressLocality, addressCountry) are missing'
                ),
            ],
            'invalid contact' => [
                [
                    'mainLanguage' => 'nl',
                    'name' => 'publiq',
                    'website' => 'https://www.publiq.be',
                    'contact' => 'invalid',
                ],
                new SchemaError(
                    '/contact',
                    'The data (string) must match the type: array'
                ),
            ],
            'invalid contact detail' => [
                [
                    'mainLanguage' => 'nl',
                    'name' => 'publiq',
                    'website' => 'https://www.publiq.be',
                    'contact' => [
                        [],
                    ],
                ],
                new SchemaError(
                    '/contact/0',
                    'The data (array) must match the type: object'
                ),
            ],
            'missing contact detail' => [
                [
                    'mainLanguage' => 'nl',
                    'name' => 'publiq',
                    'website' => 'https://www.publiq.be',
                    'contact' => [
                        [
                            'missing' => 'all fields',
                        ],
                    ],
                ],
                new SchemaError(
                    '/contact/0',
                    'The required properties (type, value) are missing'
                ),
            ],
        ];
    }
}
