<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\MediaObjectRepository;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\ImageIDParser;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * Polyfills the mediaObject/images properties on incoming JSON-LD of events, places and organizers that need to be
 * imported if they only contain an @id. Also generates an @id if missing but id is given.
 */
final class ImagesPropertyPolyfillRequestBodyParser implements RequestBodyParser
{
    private const EVENTS = 'mediaObject';
    private const PLACES = 'mediaObject';
    private const ORGANIZERS = 'images';

    private string $imagesPropertyName;
    private IriGeneratorInterface $iriGenerator;
    private MediaObjectRepository $mediaObjectRepository;

    private function __construct(
        string $imagesPropertyName,
        IriGeneratorInterface $imagesIriGenerator,
        MediaObjectRepository $mediaObjectRepository
    ) {
        $this->imagesPropertyName = $imagesPropertyName;
        $this->iriGenerator = $imagesIriGenerator;
        $this->mediaObjectRepository = $mediaObjectRepository;
    }

    public static function createForEvents(
        IriGeneratorInterface $imagesIriGenerator,
        MediaObjectRepository $mediaObjectRepository
    ): self {
        return new self(self::EVENTS, $imagesIriGenerator, $mediaObjectRepository);
    }

    public static function createForPlaces(
        IriGeneratorInterface $imagesIriGenerator,
        MediaObjectRepository $mediaObjectRepository
    ): self {
        return new self(self::PLACES, $imagesIriGenerator, $mediaObjectRepository);
    }

    public static function createForOrganizers(
        IriGeneratorInterface $imagesIriGenerator,
        MediaObjectRepository $mediaObjectRepository
    ): self {
        return new self(self::ORGANIZERS, $imagesIriGenerator, $mediaObjectRepository);
    }

    /**
     * @uses polyfillImageData
     */
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!$data instanceof stdClass) {
            return $request;
        }

        $propertyName = $this->imagesPropertyName;
        if (!isset($data->$propertyName) || !is_array($data->$propertyName)) {
            return $request;
        }

        /** @var ApiProblem[] $apiProblems */
        $apiProblems = [];

        $data->$propertyName = array_map(
            function ($imageData, int $index) use (&$apiProblems) {
                if ($imageData instanceof stdClass) {
                    try {
                        $imageData = $this->polyfillImageData($imageData, $index);
                    } catch (ApiProblem $e) {
                        $apiProblems[] = $e;
                    }
                }
                return $imageData;
            },
            $data->$propertyName,
            array_keys($data->$propertyName)
        );

        // If multiple ApiProblems are thrown, we can merge them since they will also be of the type
        // https://api.publiq.be/probs/body/invalid-data in this case.
        if (count($apiProblems) > 0) {
            $first = array_shift($apiProblems);
            foreach ($apiProblems as $apiProblem) {
                $schemaErrors = $apiProblem->getSchemaErrors();
                $first->appendSchemaErrors(...$schemaErrors);
            }
            throw $first;
        }

        return $request->withParsedBody($data);
    }

    private function polyfillImageData(stdClass $imageData, int $index): stdClass
    {
        // Polyfill @id if missing but id is set.
        if (!isset($imageData->{'@id'}) && isset($imageData->id)) {
            $imageData->{'@id'} = $this->iriGenerator->iri($imageData->id);
        }

        // If we have no @id or it's not valid at this point, we cannot polyfill any missing properties so return early.
        // These errors will be handled later by the JSON schema validation.
        if (!isset($imageData->{'@id'})) {
            return $imageData;
        }
        try {
            $imageIdUrl = new Url($imageData->{'@id'});
        } catch (InvalidArgumentException $e) {
            return $imageData;
        }
        try {
            $imageId = (new ImageIDParser())->fromUrl($imageIdUrl);
        } catch (InvalidArgumentException $e) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/' . $this->imagesPropertyName . '/' . $index . '/@id',
                    sprintf('Image with @id "%s" does not exist.', $imageIdUrl->toString())
                )
            );
        }

        // Images do not have a read model at the time of writing, so we need to read the image aggregate from the
        // event store to read its properties. If the image does not exist an ApiProblem should be thrown to avoid that
        // we import non-existing images.
        try {
            /** @var MediaObject $image */
            $image = $this->mediaObjectRepository->load($imageId->toString());
        } catch (AggregateNotFoundException $e) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/' . $this->imagesPropertyName . '/' . $index . '/@id',
                    sprintf(
                        'Image with @id "%s" (id "%s") does not exist.',
                        $imageIdUrl->toString(),
                        $imageId->toString()
                    )
                )
            );
        }

        // Only set missing properties, so a client can also include overwrites for specific properties if wanted.
        // Included properties will only be overwritten on the event/place/organizer, not on the image aggregate.
        if (!isset($imageData->description)) {
            $imageData->description = $image->getDescription()->toString();
        }
        if (!isset($imageData->copyrightHolder)) {
            $imageData->copyrightHolder = $image->getCopyrightHolder()->toString();
        }
        if (!isset($imageData->inLanguage)) {
            $imageData->inLanguage = $image->getLanguage()->getCode();
        }

        return $imageData;
    }
}
