<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\ImageUploaderInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

final class UploadMediaRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var ImageUploaderInterface&MockObject  */
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

        $imageId = new Uuid('08d9df2e-091d-4f65-930b-00f565a9158f');

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
    public function it_throws_an_api_problem_if_a_field_is_missing_or_invalid(array $body, ApiProblem $apiProblem): void
    {
        $uploadedFile = $this->createUploadedFile('ABC', UPLOAD_ERR_OK, 'test.txt', 'text/plain');

        $request = (new Psr7RequestBuilder())
            ->withParsedBody($body)
            ->withFiles(['file' => $uploadedFile])
            ->build('POST');

        $this->assertCallableThrowsApiProblem($apiProblem, fn () => $this->uploadMediaRequestHandler->handle($request));
    }

    public function incompleteRequestProvider(): array
    {
        return  [
            'missing description' => [
                [
                    'copyrightHolder' => ' Dwight Hooker',
                    'language' => 'nl',
                ],
                ApiProblem::bodyInvalidDataWithDetail('Form data field "description" is required.'),
            ],
            'missing copyright holder' => [
                [
                    'description' => 'Lenna',
                    'language' => 'nl',
                ],
                ApiProblem::bodyInvalidDataWithDetail('Form data field "copyrightHolder" is required.'),
            ],
            'copyright holder empty' => [
                [
                    'description' => 'Lenna',
                    'language' => 'nl',
                    'copyrightHolder' => '',
                ],
                ApiProblem::bodyInvalidDataWithDetail('Form data field "copyrightHolder" is invalid: Given string should not be empty.'),
            ],
            'copyright holder too short' => [
                [
                    'description' => 'Lenna',
                    'language' => 'nl',
                    'copyrightHolder' => 'a',
                ],
                ApiProblem::bodyInvalidDataWithDetail('Form data field "copyrightHolder" is invalid: CopyrightHolder \'a\' should not be shorter than 2 chars.'),
            ],
            'copyright holder too long' => [
                [
                    'description' => 'Lenna',
                    'language' => 'nl',
                    'copyrightHolder' => str_repeat('a', 251),
                ],
                ApiProblem::bodyInvalidDataWithDetail('Form data field "copyrightHolder" is invalid: CopyrightHolder \'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa\' should not be longer than 250 chars.'),
            ],
            'missing language' => [
                [
                    'description' => 'Lenna',
                    'copyrightHolder' => ' Dwight Hooker',
                ],
                ApiProblem::bodyInvalidDataWithDetail('Form data field "language" is required.'),
            ],
            'invalid language' => [
                [
                    'description' => 'Lenna',
                    'copyrightHolder' => ' Dwight Hooker',
                    'language' => 'foo',
                ],
                ApiProblem::bodyInvalidDataWithDetail('Form data field "language" is must be exactly 2 lowercase letters long (for example "nl").'),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_if_no_file_is_uploaded(): void
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

    /**
     * @test
     * @bugfix https://jira.uitdatabank.be/browse/III-5005
     */
    public function it_throws_if_a_file_was_uploaded_with_the_wrong_form_data_name(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withParsedBody([
                'description' => 'Lenna',
                'copyrightHolder' => ' Dwight Hooker',
                'language' => 'nl',
            ])
            ->withFiles(
                ['another_file' => $this->createUploadedFile('ABC', UPLOAD_ERR_OK, 'test.txt', 'text/plain')]
            )
            ->build('POST');

        $this->expectException(ApiProblem::class);
        $this->expectExceptionMessage('File missing');

        $this->uploadMediaRequestHandler->handle($request);
    }

    private function createUploadedFile(
        string $content,
        int $error,
        string $clientFileName,
        string $clientMediaType
    ): UploadedFile {
        $filePath = tempnam(sys_get_temp_dir(), uniqid('', true));
        file_put_contents($filePath, $content);

        return new UploadedFile($filePath, filesize($filePath), $error, $clientFileName, $clientMediaType);
    }
}
