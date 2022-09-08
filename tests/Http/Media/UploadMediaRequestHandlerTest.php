<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\ImageUploaderInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use Zend\Diactoros\UploadedFile;

final class UploadMediaRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var ImageUploaderInterface|MockObject  */
    private $imageUploader;

    private UploadMediaRequestHandler $uploadMediaRequestHandler;

    public function setUp(): void
    {
        $this->imageUploader = $this->createMock(ImageUploaderInterface::class);

        $this->uploadMediaRequestHandler = new UploadMediaRequestHandler(
            $this->imageUploader,
            new CallableIriGenerator(fn (string $item) => 'https://io.uitdatabank.dev/images/' . $item)
        );
    }

    /**
     * @test
     */
    public function it_handles_uploading_an_image(): void
    {
        $uploadedFile = $this->createUploadedFile('ABC', UPLOAD_ERR_OK, 'test.txt', 'text/plain');

        $request = (new Psr7RequestBuilder())
            ->withParsedBody([
                'description' => 'Lenna',
                'copyrightHolder' => ' Dwight Hooker',
                'language' => 'nl',
            ])
            ->withFiles(['file' => $uploadedFile])
            ->build('POST');

        $imageId = new UUID('08d9df2e-091d-4f65-930b-00f565a9158f');

        $this->imageUploader
            ->expects($this->once())
            ->method('upload')
            ->willReturnCallback(function (UploadedFileInterface $uploadedFile) use ($imageId) {
                $this->assertEquals('test.txt', $uploadedFile->getClientFilename());
                $this->assertEquals('text/plain', $uploadedFile->getClientMediaType());
                $this->assertEquals(UPLOAD_ERR_OK, $uploadedFile->getError());
                $this->assertEquals(3, $uploadedFile->getSize());

                return $imageId;
            });

        $response = $this->uploadMediaRequestHandler->handle($request);

        $expectedResponseContent = Json::encode([
            '@id' => 'https://io.uitdatabank.dev/images/' . $imageId->toString(),
            'imageId' => $imageId->toString(),
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($expectedResponseContent, $response->getBody());
    }

    /**
     * @test
     * @dataProvider incompleteRequestProvider
     */
    public function it_returns_400_if_file_is_missing(array $body, JsonResponse $expectedResponse): void
    {
        $uploadedFile = $this->createUploadedFile('ABC', UPLOAD_ERR_OK, 'test.txt', 'text/plain');

        $request = (new Psr7RequestBuilder())
            ->withParsedBody($body)
            ->withFiles(['file' => $uploadedFile])
            ->build('POST');

        $response = $this->uploadMediaRequestHandler->handle($request);

        $this->assertEquals($expectedResponse->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($expectedResponse->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function incompleteRequestProvider(): array
    {
        return  [
            'missing description' => [
                [
                    'copyrightHolder' => ' Dwight Hooker',
                    'language' => 'nl',
                ],
                new JsonResponse(['error' => 'description required'], 400),
            ],
            'missing copyright holder' => [
                [
                    'description' => 'Lenna',
                    'language' => 'nl',
                ],
                new JsonResponse(['error' => 'copyright holder required'], 400),
            ],
            'missing language' => [
                [
                    'description' => 'Lenna',
                    'copyrightHolder' => ' Dwight Hooker',
                ],
                new JsonResponse(['error' => 'language required'], 400),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_if_description_is_missing(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withParsedBody([
                'description' => 'Lenna',
                'copyrightHolder' => ' Dwight Hooker',
                'language' => 'nl',
            ])
            ->build('POST');

        $this->expectException(ApiProblem::class);
        $this->expectExceptionMessage('File missing');

        $this->uploadMediaRequestHandler->handle($request);
    }

    private function createUploadedFile($content, $error, $clientFileName, $clientMediaType): UploadedFile
    {
        $filePath = tempnam(sys_get_temp_dir(), uniqid('', true));
        file_put_contents($filePath, $content);

        return new UploadedFile($filePath, filesize($filePath), $error, $clientFileName, $clientMediaType);
    }
}
