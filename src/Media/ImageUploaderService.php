<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Commands\UploadImage;
use CultuurNet\UDB3\Media\Exceptions\InvalidFileSize;
use CultuurNet\UDB3\Media\Exceptions\InvalidFileType;
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

    private array $supportedMimeTypes = [
        'image/png',
        'image/jpeg',
        'image/gif',
    ];

    public function __construct(
        UuidGeneratorInterface $uuidGenerator,
        CommandBus $commandBus,
        FilesystemOperator $filesystem,
        string $uploadDirectory,
        int $maxFileSize = null
    ) {
        if ($maxFileSize < 0) {
            throw new \RuntimeException('Max file size should be 0 or bigger inside config.yml.');
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
            throw new InvalidFileType('The file did not upload correctly.');
        }

        $mimeTypeString = $file->getMimeType();

        if (!$mimeTypeString) {
            throw new InvalidFileType('The type of the uploaded file can not be guessed.');
        }

        $this->guardFileSizeLimit($file);

        if (!\in_array($mimeTypeString, $this->supportedMimeTypes, true)) {
            throw new InvalidFileType(
                'The uploaded file has mime type "' . $mimeTypeString . '" instead of ' . \implode(',', $this->supportedMimeTypes)
            );
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
            throw new \RuntimeException('The size of the uploaded image could not be determined.');
        }

        if ($this->maxFileSize && $fileSize > $this->maxFileSize) {
            throw new InvalidFileSize(
                'The file size of the uploaded image is too big. File size (bytes): ' . $fileSize . ' Max size (bytes):' . $this->maxFileSize
            );
        }
    }

    public function getUploadDirectory(): string
    {
        return $this->uploadDirectory;
    }
}
