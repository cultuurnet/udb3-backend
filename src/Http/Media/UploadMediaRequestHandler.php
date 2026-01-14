<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\ImageDownloader;
use CultuurNet\UDB3\Media\ImageUploaderInterface;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UploadMediaRequestHandler implements RequestHandlerInterface
{
    private ImageUploaderInterface $imageUploader;

    private ImageDownloader $imageDownloader;
    private IriGeneratorInterface $iriGenerator;

    public function __construct(
        ImageUploaderInterface $imageUploader,
        ImageDownloader $imageDownloader,
        IriGeneratorInterface $iriGenerator
    ) {
        $this->imageUploader = $imageUploader;
        $this->imageDownloader = $imageDownloader;
        $this->iriGenerator = $iriGenerator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $contentTypeHeaders = $request->getHeader('Content-Type');
        if (empty($contentTypeHeaders) || !str_contains($contentTypeHeaders[0], 'multipart/form-data')) {
            $imageId = $this->handleJsonBody($request);
        } else {
            $imageId = $this->handleFormData($request);
        }

        return new JsonResponse(
            [
                '@id' => $this->iriGenerator->iri($imageId->toString()),
                'imageId' => $imageId->toString(),
            ],
            201
        );
    }

    private function handleFormData(ServerRequestInterface $request): Uuid
    {
        $uploadedFiles = $request->getUploadedFiles();
        if (!isset($uploadedFiles['file']) || !$uploadedFiles['file'] instanceof UploadedFileInterface) {
            throw ApiProblem::fileMissing('The file property is required');
        }
        $uploadedFile = $uploadedFiles['file'];

        if (count($request->getUploadedFiles()) > 1) {
            throw ApiProblem::fileMissing('Only one file is allowed');
        }

        $parsedBody = $request->getParsedBody();
        $description = $parsedBody['description'] ?? null;
        $copyrightHolder = $parsedBody['copyrightHolder'] ?? null;
        $language = $parsedBody['language'] ?? null;

        if (!$description) {
            throw ApiProblem::bodyInvalidDataWithDetail('Form data field "description" is required.');
        }

        if ($copyrightHolder === null) {
            throw ApiProblem::bodyInvalidDataWithDetail('Form data field "copyrightHolder" is required.');
        }

        try {
            $copyrightHolder = new CopyrightHolder($copyrightHolder);
        } catch (InvalidArgumentException $e) {
            throw ApiProblem::bodyInvalidDataWithDetail('Form data field "copyrightHolder" is invalid: ' . $e->getMessage());
        }

        if (!$language) {
            throw ApiProblem::bodyInvalidDataWithDetail('Form data field "language" is required.');
        }

        try {
            $language = new Language($language);
        } catch (InvalidArgumentException $e) {
            throw ApiProblem::bodyInvalidDataWithDetail('Form data field "language" is must be exactly 2 lowercase letters long (for example "nl").');
        }

        return $this->imageUploader->upload(
            $uploadedFile,
            new Description($description),
            $copyrightHolder,
            $language
        );
    }

    private function handleJsonBody(ServerRequestInterface $request): Uuid
    {
        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::IMAGE_POST)
        );

        /** @var \stdClass $data */
        $data = $parser->parse($request)->getParsedBody();

        $contentUrl = new Url($data->contentUrl);
        $description = new Description($data->description);
        $copyrightHolder = new CopyrightHolder($data->copyrightHolder);
        $language = new Language($data->inLanguage);

        $uploadedFile = $this->imageDownloader->download($contentUrl);

        return $this->imageUploader->upload(
            $uploadedFile,
            $description,
            $copyrightHolder,
            $language
        );
    }
}
