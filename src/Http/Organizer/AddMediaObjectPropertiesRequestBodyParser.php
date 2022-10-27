<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Media\MediaObject;
use Psr\Http\Message\ServerRequestInterface;

final class AddMediaObjectPropertiesRequestBodyParser implements RequestBodyParser
{
    private Repository $mediaRepository;

    private string $idField;

    public function __construct(Repository $mediaRepository, string $idField = 'id')
    {
        $this->mediaRepository = $mediaRepository;
        $this->idField = $idField;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = (array) $request->getParsedBody();

        $imageId = $data[$this->idField];

        try {
            /** @var MediaObject $mediaObject */
            $mediaObject = $this->mediaRepository->load($imageId);
        } catch (AggregateNotFoundException $exception) {
            throw ApiProblem::imageNotFound($imageId);
        }

        $convertedData = [];
        $convertedData[$this->idField] = $imageId;
        $convertedData['language'] = $data['language'] ?? $mediaObject->getLanguage()->getCode();
        $convertedData['description'] = $data['description'] ?? $mediaObject->getDescription()->toNative();
        $convertedData['copyrightHolder'] = $data['copyrightHolder'] ?? $mediaObject->getCopyrightHolder()->toString();

        return $request->withParsedBody((object)$convertedData);
    }
}
