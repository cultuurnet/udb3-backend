<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Import;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Request;

class ImportRestControllerTest extends TestCase
{
    /**
     * @var QueryParameterApiKeyReader
     */
    private $apiKeyReader;

    /**
     * @var InMemoryConsumerRepository
     */
    private $consumerRepository;

    /**
     * @var ApiKey
     */
    private $apiKey;

    /**
     * @var ConsumerInterface|MockObject
     */
    private $consumer;

    /**
     * @var DocumentImporterInterface|MockObject
     */
    private $importer;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    /**
     * @var CallableIriGenerator
     */
    private $iriGenerator;

    /**
     * @var ImportRestController
     */
    private $controller;

    public function setUp()
    {
        $this->apiKeyReader = new QueryParameterApiKeyReader('apiKey');
        $this->consumerRepository = new InMemoryConsumerRepository();

        $this->apiKey = new ApiKey('7f037576-e7e3-460a-8e77-87d2b731b12a');
        $this->consumer = $this->createMock(ConsumerInterface::class);

        $this->consumerRepository->setConsumer($this->apiKey, $this->consumer);

        $this->importer = $this->createMock(DocumentImporterInterface::class);

        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);

        $this->iriGenerator = new CallableIriGenerator(
            function ($item) {
                return 'https://io.uitdatabank.be/mock/' . $item;
            }
        );

        $this->controller = new ImportRestController(
            $this->apiKeyReader,
            $this->consumerRepository,
            $this->importer,
            $this->uuidGenerator,
            $this->iriGenerator,
            'mockId'
        );
    }

    /**
     * @test
     */
    public function it_should_set_the_id_url_on_the_json_body_and_import_the_document()
    {
        $id = 'c25ea5b8-acd2-4987-a207-6ee11444fde8';
        $json = json_encode(
            [
                'name' => [
                    'nl' => 'Voorbeeld naam',
                ],
            ]
        );
        $request = Request::create('www.uitdatabank.dev', 'GET', [], [], [], [], $json);

        $expectedDocument = new DecodedDocument(
            $id,
            [
                '@id' => 'https://io.uitdatabank.be/mock/c25ea5b8-acd2-4987-a207-6ee11444fde8',
                'name' => [
                    'nl' => 'Voorbeeld naam',
                ],
            ]
        );

        $this->importer->expects($this->once())
            ->method('import')
            ->with($expectedDocument);

        $response = $this->controller->importWithId($request, $id);

        $expected = json_encode(['mockId' => $id]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expected, $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_make_the_importer_aware_of_the_consumer_if_one_could_be_identified()
    {
        $id = 'c25ea5b8-acd2-4987-a207-6ee11444fde8';
        $json = json_encode(
            [
                'name' => [
                    'nl' => 'Voorbeeld naam',
                ],
            ]
        );
        $request = Request::create(
            'www.uitdatabank.dev',
            'GET',
            ['apiKey' => $this->apiKey->toNative()],
            [],
            [],
            [],
            $json
        );

        $expectedDocument = new DecodedDocument(
            $id,
            [
                '@id' => 'https://io.uitdatabank.be/mock/c25ea5b8-acd2-4987-a207-6ee11444fde8',
                'name' => [
                    'nl' => 'Voorbeeld naam',
                ],
            ]
        );

        $this->importer->expects($this->once())
            ->method('import')
            ->with($expectedDocument, $this->consumer);

        $response = $this->controller->importWithId($request, $id);

        $expected = json_encode(['mockId' => $id]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expected, $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_override_the_id_url_on_the_json_body_and_import_the_document()
    {
        $id = 'c25ea5b8-acd2-4987-a207-6ee11444fde8';
        $json = json_encode(
            [
                '@id' => 'http://io.uitdatabank.be/mock/8e83a8df-30a7-4b4c-b250-658c63fc7db0',
                'name' => [
                    'nl' => 'Voorbeeld naam',
                ],
            ]
        );
        $request = Request::create('www.uitdatabank.dev', 'GET', [], [], [], [], $json);

        $expectedDocument = new DecodedDocument(
            $id,
            [
                '@id' => 'https://io.uitdatabank.be/mock/c25ea5b8-acd2-4987-a207-6ee11444fde8',
                'name' => [
                    'nl' => 'Voorbeeld naam',
                ],
            ]
        );

        $this->importer->expects($this->once())
            ->method('import')
            ->with($expectedDocument);

        $response = $this->controller->importWithId($request, $id);

        $expected = json_encode(['mockId' => $id]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expected, $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_generate_a_missing_id_and_import_the_document()
    {
        $generatedId = '906572e1-e189-4b34-bbe3-b154e5ff553c';
        $this->uuidGenerator->expects($this->any())
            ->method('generate')
            ->willReturn($generatedId);

        $json = json_encode(
            [
                'name' => [
                    'nl' => 'Voorbeeld naam',
                ],
            ]
        );
        $request = Request::create('www.uitdatabank.dev', 'GET', [], [], [], [], $json);

        $expectedDocument = new DecodedDocument(
            $generatedId,
            [
                '@id' => 'https://io.uitdatabank.be/mock/' . $generatedId,
                'name' => [
                    'nl' => 'Voorbeeld naam',
                ],
            ]
        );

        $this->importer->expects($this->once())
            ->method('import')
            ->with($expectedDocument);

        $response = $this->controller->importWithoutId($request);

        $expected = json_encode(['mockId' => $generatedId]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expected, $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_importing_with_id_and_no_json()
    {
        $request = Request::create('www.uitdatabank.dev');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('JSON-LD missing.');

        $this->controller->importWithoutId($request);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_importing_without_id_and_no_json()
    {
        $request = Request::create('www.uitdatabank.dev');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('JSON-LD missing.');

        $this->controller->importWithId($request, '2f66ac1d-fd5a-431e-ba72-821da4176391');
    }
}
