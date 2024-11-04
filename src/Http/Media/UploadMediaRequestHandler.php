<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\ImageUploaderInterface;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use InvalidArgumentException;
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

        $imageId = $this->imageUploader->upload(
            $uploadedFile,
            new Description($description),
            $copyrightHolder,
            $language
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
