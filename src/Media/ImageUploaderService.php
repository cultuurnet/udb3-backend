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
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

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
        'image/png' => 'png',
        'image/jpeg' => 'jpeg',
        'image/gif' => 'gif',
    ];

    public function __construct(
        UuidGeneratorInterface $uuidGenerator,
        CommandBus $commandBus,
        FilesystemOperator $filesystem,
        string $uploadDirectory,
        int $maxFileSize = null
    ) {
        if ($maxFileSize < 0) {
            throw new RuntimeException('Max file size should be 0 or higher if not null.');
        }

        $this->uuidGenerator = $uuidGenerator;
        $this->commandBus = $commandBus;
        $this->filesystem = $filesystem;
        $this->uploadDirectory = $uploadDirectory;
        $this->maxFileSize = $maxFileSize;
    }

    public function upload(
        UploadedFileInterface $file,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder,
        Language $language
    ): UUID {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new InvalidFileType('The file did not upload correctly.');
        }

        $mimeTypeString = $file->getClientMediaType();
        if (!$mimeTypeString) {
            throw new InvalidFileType('The type of the uploaded file can not be guessed.');
        }

        $supportedMimeTypes = array_keys($this->supportedMimeTypes);
        if (!\in_array($mimeTypeString, $supportedMimeTypes, true)) {
            throw new InvalidFileType(
                'The uploaded file has mime type "' . $mimeTypeString . '" instead of ' . \implode(',', $supportedMimeTypes)
            );
        }

        $this->guardFileSizeLimit($file);

        $mimeType = MIMEType::fromNative($mimeTypeString);

        $fileId = new UUID($this->uuidGenerator->generate());
        $fileName = $fileId->toString() . '.' . $this->guessExtensionForMimeType($file->getClientMediaType());
        $destination = $this->getUploadDirectory() . '/' . $fileName;
        $this->filesystem->write($destination, $file->getStream()->getContents());

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

    private function guardFileSizeLimit(UploadedFileInterface $file): void
    {
        $fileSize = $file->getSize();

        if ($fileSize === null) {
            throw new RuntimeException('The size of the uploaded image could not be determined.');
        }

        if ($fileSize === 0) {
            throw new InvalidFileSize('The size of the uploaded image must not be 0 bytes.');
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

    private function guessExtensionForMimeType(string $mimeType): ?string
    {
        return $this->supportedMimeTypes[$mimeType] ?? null;
    }
}
