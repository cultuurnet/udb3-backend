<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Commands\UploadImage;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class MediaManager extends Udb3CommandHandler implements LoggerAwareInterface, MediaManagerInterface
{
    use LoggerAwareTrait;

    private IriGeneratorInterface $iriGenerator;

    private PathGeneratorInterface $pathGenerator;

    private Repository $repository;

    private ImageStorage $imageStorage;

    public function __construct(
        IriGeneratorInterface $iriGenerator,
        PathGeneratorInterface $pathGenerator,
        Repository $repository,
        ImageStorage $imageStorage
    ) {
        $this->iriGenerator = $iriGenerator;
        $this->pathGenerator = $pathGenerator;
        $this->repository = $repository;
        $this->imageStorage = $imageStorage;

        // Avoid conditional log calls by setting a null logger by default.
        $this->setLogger(new NullLogger());
    }

    public function create(
        UUID $id,
        MIMEType $fileType,
        Description $description,
        CopyrightHolder $copyrightHolder,
        Url $sourceLocation,
        Language $language
    ): MediaObject {
        try {
            /** @var MediaObject $existingMediaObject */
            $existingMediaObject = $this->repository->load($id->toString());
            $this->logger->info('Trying to create media with id: ' . $id->toString() . ' but it already exists. Using existing Media Object!');

            return $existingMediaObject;
        } catch (AggregateNotFoundException $exception) {
            $this->logger->info('No existing media with id: ' . $id->toString() . ' found. Creating a new Media Object!');
        }

        $mediaObject = MediaObject::create(
            $id,
            $fileType,
            $description,
            $copyrightHolder,
            $sourceLocation,
            $language
        );

        $this->repository->save($mediaObject);

        return $mediaObject;
    }

    public function handleUploadImage(UploadImage $uploadImage): void
    {
        $pathParts = explode('/', $uploadImage->getFilePath());
        $fileName = array_pop($pathParts);
        $fileNameParts = explode('.', $fileName);
        $extension = array_pop($fileNameParts);
        $destinationPath = $this->pathGenerator->path(
            $uploadImage->getFileId(),
            $extension
        );

        $destinationIri = $this->iriGenerator->iri($destinationPath);

        $this->imageStorage->store($uploadImage->getFilePath(), $destinationPath);

        $this->create(
            $uploadImage->getFileId(),
            $uploadImage->getMimeType(),
            $uploadImage->getDescription(),
            $uploadImage->getCopyrightHolder(),
            new Url($destinationIri),
            $uploadImage->getLanguage()
        );

        $jobInfo = ['file_id' => $uploadImage->getFileId()->toString()];
        $this->logger->info('job_info', $jobInfo);
    }

    public function get(UUID $fileId): MediaObject
    {
        try {
            /** @var MediaObject $mediaObject */
            $mediaObject = $this->repository->load($fileId->toString());
        } catch (AggregateNotFoundException $e) {
            throw new MediaObjectNotFoundException(
                sprintf("Media object with id '%s' not found", $fileId->toString()),
                0,
                $e
            );
        }

        return $mediaObject;
    }

    public function getImage(UUID $imageId): Image
    {
        $mediaObject = $this->get($imageId);

        return new Image(
            $mediaObject->getMediaObjectId(),
            $mediaObject->getMimeType(),
            $mediaObject->getDescription(),
            $mediaObject->getCopyrightHolder(),
            $mediaObject->getSourceLocation(),
            new \CultuurNet\UDB3\Model\ValueObject\Translation\Language($mediaObject->getLanguage()->toString())
        );
    }
}
