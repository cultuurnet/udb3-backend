<?php

namespace CultuurNet\UDB3\Model\Import\Command;

use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class HttpImportCommandHandlerTest extends TestCase
{
    /**
     * @var string
     */
    private $commandClassName;

    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * @var ClientInterface|MockObject
     */
    private $httpClient;

    /**
     * @var HttpImportCommandHandler
     */
    private $commandHandler;

    public function setUp()
    {
        $this->commandClassName = ImportEventDocument::class;
        $this->iriGenerator = new CallableIriGenerator(
            function ($item) {
                return 'https://io.uitdatabank.be/events/' . $item;
            }
        );
        $this->httpClient = $this->createMock(ClientInterface::class);

        $this->commandHandler = new HttpImportCommandHandler(
            $this->commandClassName,
            $this->iriGenerator,
            $this->httpClient
        );
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_set_up_with_an_unsupported_class_name()
    {
        $this->expectException(\InvalidArgumentException::class);

        new HttpImportCommandHandler(
            new \stdClass(),
            $this->iriGenerator,
            $this->httpClient
        );
    }

    /**
     * @test
     */
    public function it_should_ignore_unsupported_commands()
    {
        $command = new \stdClass();
        $command->documentId = 'dc831b45-3368-4657-a85c-03bb302d5ec2';
        $command->documentUrl = 'https://io.uitdatabank.be/events/dc831b45-3368-4657-a85c-03bb302d5ec2';
        $command->jwt = 'foo.bar.acme';
        $command->apiKey = '24b5bebe-d369-4e17-aaab-6d6b5ff6ad06';

        $this->httpClient->expects($this->never())
            ->method('put');

        $this->commandHandler->handle($command);
    }

    /**
     * @test
     */
    public function it_should_fetch_the_document_body_and_send_it_to_the_import_api()
    {
        $documentId = 'f9aec59a-8f70-41ac-bcd5-16020de59afd';
        $documentUrl = 'https://io.uitdatabank.be/events/dc831b45-3368-4657-a85c-03bb302d5ec2';
        $jwt = 'foo.bar.acme';
        $apiKey = '24b5bebe-d369-4e17-aaab-6d6b5ff6ad06';

        $command = new ImportEventDocument(
            $documentId,
            $documentUrl,
            $jwt,
            $apiKey
        );

        $json = json_encode(
            [
                '@id' => 'https://io.uitdatabank.be/events/dc831b45-3368-4657-a85c-03bb302d5ec2',
                '@type' => 'Event',
                'name' => [
                    'nl' => 'Voorbeeld naam',
                ],
                'etc' => '...',
            ]
        );

        $getRequest = $this->createMock(RequestInterface::class);
        $getResponse = $this->createMock(ResponseInterface::class);

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with($documentUrl)
            ->willReturn($getRequest);

        $getRequest->expects($this->once())
            ->method('send')
            ->willReturn($getResponse);

        $getResponse->expects($this->once())
            ->method('getBody')
            ->with(true)
            ->willReturn($json);

        $putRequest = $this->createMock(RequestInterface::class);

        $this->httpClient->expects($this->once())
            ->method('put')
            ->with(
                'https://io.uitdatabank.be/events/f9aec59a-8f70-41ac-bcd5-16020de59afd',
                [
                    'Authorization' => 'Bearer ' . $jwt,
                    'X-Api-Key' => $apiKey,
                ],
                $json
            )
            ->willReturn($putRequest);

        $putRequest->expects($this->once())
            ->method('send');

        $this->commandHandler->handle($command);
    }
}
