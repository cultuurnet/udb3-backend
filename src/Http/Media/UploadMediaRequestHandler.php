<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\ImageUploaderInterface;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\StringLiteral;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UploadMediaRequestHandler implements RequestHandlerInterface
{
    private ImageUploaderInterface $imageUploader;
    private IriGeneratorInterface $iriGenerator;

    public function __construct(ImageUploaderInterface $imageUploader, IriGeneratorInterface $iriGenerator)
    {
        $this->imageUploader = $imageUploader;
        $this->iriGenerator = $iriGenerator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
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

        if (!$copyrightHolder) {
            throw ApiProblem::bodyInvalidDataWithDetail('Form data field "copyrightHolder" is required.');
        }

        if (strlen($copyrightHolder) < 2) {
            throw ApiProblem::bodyInvalidDataWithDetail('Form data field "copyrightHolder" must be at least 2 characters long.');
        }

        if (!$language) {
            throw ApiProblem::bodyInvalidDataWithDetail('Form data field "language" is required.');
        }

        $imageId = $this->imageUploader->upload(
            $uploadedFile,
            new StringLiteral($description),
            new CopyrightHolder($copyrightHolder),
            new Language($language)
        );

        return new JsonResponse(
            [
                '@id' => $this->iriGenerator->iri($imageId->toString()),
                'imageId' => $imageId->toString(),
            ],
            201
        );
    }
}
