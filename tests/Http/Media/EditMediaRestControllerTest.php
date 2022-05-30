<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\ImageUploaderInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EditMediaRestControllerTest extends TestCase
{
    private MockObject $imageUploader;
    private EditMediaRestController $controller;

    public function setUp(): void
    {
        $this->imageUploader = $this->createMock(ImageUploaderInterface::class);

        $this->controller = new EditMediaRestController(
            $this->imageUploader,
            new CallableIriGenerator(fn (string $item) => 'https://io.uitdatabank.dev/images/' . $item)
        );
    }

    /**
     * @test
     * @dataProvider incompleteUploadRequestsProvider
     */
    public function it_should_return_an_error_response_when_media_meta_data_is_missing_for_an_upload(
        Request $uploadRequest,
        Response $expectedErrorResponse
    ): void {
        $response = $this->controller->upload($uploadRequest);

        $this->assertEquals($expectedErrorResponse->getContent(), $response->getContent());
    }

    public function incompleteUploadRequestsProvider(): array
    {
        return [
            'missing description' => [
                'request' => new Request(
                    [],
                    [
                        'copyrightHolder' => 'Danny',
                        'language' => 'nl',
                    ],
                    [],
                    [],
                    [
                        'file' => $this->createMock(UploadedFile::class),
                    ]
                ),
                'response' => new JsonResponse(['error' => 'description required'], 400),
            ],
            'missing language' => [
                'request' => new Request(
                    [],
                    [
                        'copyrightHolder' => ' Dwight Hooker',
                        'description' => 'Lenna',
                    ],
                    [],
                    [],
                    [
                        'file' => $this->createMock(UploadedFile::class),
                    ]
                ),
                'response' => new JsonResponse(['error' => 'language required'], 400),
            ],
            'copyright holder language' => [
                'request' => new Request(
                    [],
                    [
                        'description' => 'Lenna',
                        'language' => 'nl',
                    ],
                    [],
                    [],
                    [
                        'file' => $this->createMock(UploadedFile::class),
                    ]
                ),
                'response' => new JsonResponse(['error' => 'copyright holder required'], 400),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throw_file_missing_api_problem_on_empty_file(): void
    {
        $this->expectException(ApiProblem::class);
        $this->expectExceptionMessage('File missing');

        $this->controller->upload(
            new Request(
                [],
                [
                    'copyrightHolder' => ' Dwight Hooker',
                    'description' => 'Lenna',
                    'language' => 'nl',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_should_pass_along_upload_data_to_the_image_uploader_create_a_job_and_return_a_command_id(): void
    {
        $uploadRequest = new Request(
            [],
            [
                'description' => 'Lenna',
                'copyrightHolder' => ' Dwight Hooker',
                'language' => 'nl',
            ],
            [],
            [],
            [
                'file' => $this->createMock(UploadedFile::class),
            ]
        );

        $imageId = new UUID('08d9df2e-091d-4f65-930b-00f565a9158f');

        $this->imageUploader
            ->expects($this->once())
            ->method('upload')
            ->willReturn($imageId);

        $response = $this->controller->upload($uploadRequest);

        $expectedResponseContent = Json::encode([
            '@id' => 'https://io.uitdatabank.dev/images/' . $imageId->toString(),
            'imageId' => $imageId->toString(),
        ]);

        $this->assertEquals($expectedResponseContent, $response->getContent());
    }
}
