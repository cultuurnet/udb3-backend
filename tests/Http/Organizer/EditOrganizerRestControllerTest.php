<?php

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintException;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Organizer\OrganizerEditingServiceInterface;
use CultuurNet\UDB3\Title;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Geography\Country;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class EditOrganizerRestControllerTest extends TestCase
{
    /**
     * @var OrganizerEditingServiceInterface|MockObject
     */
    private $editService;

    /**
     * @var IriGeneratorInterface|MockObject
     */
    private $iriGenerator;

    /**
     * @var EditOrganizerRestController
     */
    private $controller;

    public function setUp()
    {
        $this->editService = $this->createMock(OrganizerEditingServiceInterface::class);

        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);
        $this->iriGenerator->expects($this->any())
            ->method('iri')
            ->willReturnCallback(
                function ($organizerId) {
                    return 'http://io.uitdatabank.be/organizer/' . $organizerId;
                }
            );

        $this->controller = new EditOrganizerRestController($this->editService, $this->iriGenerator);
    }

    /**
     * @test
     */
    public function it_creates_an_organizer()
    {
        $organizerId = '123';
        $url = $this->iriGenerator->iri($organizerId);

        $this->editService->expects($this->once())
            ->method('create')
            ->with(
                new Language('en'),
                Url::fromNative('http://www.hetdepot.be/'),
                new Title('Het Depot'),
                new Address(
                    new Street('Martelarenplein 12'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                ),
                new ContactPoint(
                    [
                        "+32 498 71 49 96"
                    ],
                    [
                        "jos@hetdepot.be"
                    ],
                    [
                        "https://www.facebook.com/hetdepot"
                    ]
                )
            )
            ->willReturn($organizerId);

        $expectedResponseData = [
            'organizerId' => $organizerId,
            'url' => $url,
        ];

        $expectedResponseJson = json_encode($expectedResponseData);

        $request = $this->createRequest('POST', 'organizer_create.json');
        $response = $this->controller->create($request);
        $actualResponseJson = $response->getContent();

        $this->assertEquals($expectedResponseJson, $actualResponseJson);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_trying_to_create_an_organizer_with_a_duplicate_url()
    {
        $uuid = 'c579e9f9-b43d-49b7-892e-75e55b26841e';
        $website = 'http://www.hetdepot.be/';

        $this->editService->expects($this->once())
            ->method('create')
            ->willThrowException(new UniqueConstraintException($uuid, new StringLiteral($website)));

        $expectedMessages = [
            'website' => 'Should be unique but is already in use.',
        ];

        try {
            $request = $this->createRequest('POST', 'organizer_create.json');
            $this->controller->create($request);
            $this->fail('Did not catch expected DataValidationException');
        } catch (\Exception $e) {
            /* @var DataValidationException $e */
            $this->assertInstanceOf(DataValidationException::class, $e);
            $this->assertEquals($expectedMessages, $e->getValidationMessages());
        }
    }

    /**
     * @test
     */
    public function it_updates_the_url_of_an_organizer()
    {
        $organizerId = '5e1d6fec-d0ea-4203-b466-7fb9711f3bb9';
        $url = Url::fromNative('http://www.depot.be');

        $this->editService->expects($this->once())
            ->method('updateWebsite')
            ->with(
                $organizerId,
                $url
            );

        $content = '{"url":"' . (string) $url . '"}';
        $request = new Request([], [], [], [], [], [], $content);

        $response = $this->controller->updateUrl(
            $organizerId,
            $request
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_the_name_of_an_organizer()
    {
        $organizerId = '5e1d6fec-d0ea-4203-b466-7fb9711f3bb9';
        $name = new Title('Le Depot');
        $language = new Language('fr');

        $this->editService->expects($this->once())
            ->method('updateTitle')
            ->with(
                $organizerId,
                $name,
                $language
            );

        $content = '{"name":"' . $name->toNative() . '"}';
        $request = new Request([], [], [], [], [], [], $content);

        $response = $this->controller->updateName(
            $organizerId,
            'fr',
            $request
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_supports_deprecated_update_name_of_organizer()
    {
        $organizerId = '5e1d6fec-d0ea-4203-b466-7fb9711f3bb9';
        $name = new Title('Het Depot');

        $this->editService->expects($this->once())
            ->method('updateTitle')
            ->with(
                $organizerId,
                $name,
                new Language('nl')
            );

        $content = '{"name":"' . $name->toNative() . '"}';
        $request = new Request([], [], [], [], [], [], $content);

        $response = $this->controller->updateNameDeprecated(
            $organizerId,
            $request
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_address_of_an_organizer()
    {
        $organizerId = '5e1d6fec-d0ea-4203-b466-7fb9711f3bb9';
        $address = new Address(
            new Street('Martelarenplein 12'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $this->editService->expects($this->once())
            ->method('updateAddress')
            ->with(
                $organizerId,
                $address,
                new Language('nl')
            );

        $request = $this->createRequest(
            Request::METHOD_PUT,
            'organizer_update_address.json'
        );
        $response = $this->controller->updateAddress(
            $organizerId,
            'nl',
            $request
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_supports_deprecated_update_address_of_an_organizer()
    {
        $organizerId = '5e1d6fec-d0ea-4203-b466-7fb9711f3bb9';
        $address = new Address(
            new Street('Martelarenplein 12'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $this->editService->expects($this->once())
            ->method('updateAddress')
            ->with(
                $organizerId,
                $address,
                new Language('nl')
            );

        $request = $this->createRequest(
            Request::METHOD_PUT,
            'organizer_update_address.json'
        );
        $response = $this->controller->updateAddressDeprecated(
            $organizerId,
            $request
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_contact_point_of_an_organizer()
    {
        $organizerId = '5e1d6fec-d0ea-4203-b466-7fb9711f3bb9';
        $contactPoint = new ContactPoint(
            [
                "+32 498 71 49 96"
            ],
            [
                "jos@hetdepot.be",
                "info@hetdepot.be"
            ],
            [
                "https://www.facebook.com/hetdepot",
                "https://www.depot.be"
            ]
        );

        $this->editService->expects($this->once())
            ->method('updateContactPoint')
            ->with(
                $organizerId,
                $contactPoint
            );

        $request = $this->createRequest(
            Request::METHOD_PUT,
            'organizer_update_contact_point.json'
        );
        $response = $this->controller->updateContactPoint(
            $organizerId,
            $request
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_deletes_an_organizer()
    {
        $cdbId = '123';

        $this->editService->expects($this->once())
            ->method('delete')
            ->with($cdbId);

        $response = $this->controller->delete($cdbId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_adds_a_label()
    {
        $organizerId = 'organizerId';
        $labelName = 'publiq';

        $this->editService->expects($this->once())
            ->method('addLabel')
            ->with($organizerId, $labelName);

        $response = $this->controller->addLabel($organizerId, $labelName);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_removes_a_label()
    {
        $organizerId = 'organizerId';
        $labelName = 'publiq';

        $this->editService->expects($this->once())
            ->method('removeLabel')
            ->with($organizerId, $labelName);

        $response = $this->controller->removeLabel($organizerId, $labelName);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_no_cdbid_is_given_to_delete()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required field cdbid is missing');
        $this->controller->delete('');
    }

    /**
     * @param string $method
     * @param string $fileName
     * @return Request
     */
    private function createRequest($method, $fileName)
    {
        $content = $this->getJson($fileName);
        $request = new Request([], [], [], [], [], [], $content);
        $request->setMethod($method);

        return $request;
    }

    /**
     * @param string $fileName
     * @return string
     */
    private function getJson($fileName)
    {
        $json = file_get_contents(
            __DIR__ . '/samples/' . $fileName
        );

        return $json;
    }
}
