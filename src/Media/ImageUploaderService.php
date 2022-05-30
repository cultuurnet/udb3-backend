<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Commands\UploadImage;
use CultuurNet\UDB3\Media\Exceptions\ImageSizeError;
use CultuurNet\UDB3\Media\Exceptions\ImageUploadError;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\StringLiteral;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploaderService implements ImageUploaderInterface
{
    private UuidGeneratorInterface $uuidGenerator;

    private CommandBus $commandBus;

    private string $uploadDirectory;

    private FilesystemOperator $filesystem;

    /**
     *  The maximum file size in bytes.
     *  There is no limit when the file size is null.
     */
    private ?int $maxFileSize;

    public function __construct(
        UuidGeneratorInterface $uuidGenerator,
        CommandBus $commandBus,
        FilesystemOperator $filesystem,
        string $uploadDirectory,
        int $maxFileSize = null
    ) {
        if ($maxFileSize < 0) {
            throw new \InvalidArgumentException('Max file size should be 0 or bigger inside config.yml.');
        }

        $this->uuidGenerator = $uuidGenerator;
        $this->commandBus = $commandBus;
        $this->filesystem = $filesystem;
        $this->uploadDirectory = $uploadDirectory;
        $this->maxFileSize = $maxFileSize;
    }

    public function upload(
        UploadedFile $file,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder,
        Language $language
    ): UUID {
        if (!$file->isValid()) {
            throw new ImageUploadError('The file did not upload correctly.');
        }

        $mimeTypeString = $file->getMimeType();

        if (!$mimeTypeString) {
            throw new ImageUploadError('The type of the uploaded file can not be guessed.');
        }

        $this->guardFileSizeLimit($file);

        $fileTypeParts = explode('/', $mimeTypeString);
        $fileType = array_shift($fileTypeParts);
        if ($fileType !== 'image') {
            throw new ImageUploadError('The uploaded file is not an image.');
        }

        /** @var MIMEType $mimeType */
        $mimeType = MIMEType::fromNative($mimeTypeString);

        $fileId = new UUID($this->uuidGenerator->generate());
        $fileName = $fileId->toString() . '.' . $file->guessExtension();
        $destination = $this->getUploadDirectory() . '/' . $fileName;
        $stream = fopen($file->getRealPath(), 'r+');
        $this->filesystem->writeStream($destination, $stream);
        fclose($stream);

        $this->commandBus->dispatch(
            new UploadImage(
                $fileId,
                $mimeType,
                $description,
                $copyrightHolder,
                new StringLiteral($destination),
                $language
            )
        );

        return $fileId;
    }

    private function guardFileSizeLimit(UploadedFile $file): void
    {
        $filePath = $file->getRealPath();
        $fileSize = filesize($filePath);

        if ($this->maxFileSize && !$fileSize) {
            throw new ImageSizeError('There is a maximum size and we could not determine the size of the uploaded image.');
        }

        if ($this->maxFileSize && $fileSize > $this->maxFileSize) {
            throw new ImageSizeError(
                'The file size of the uploaded image is too big.'
            );
        }
    }

    public function getUploadDirectory(): string
    {
        return $this->uploadDirectory;
    }
}
