<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Media\Commands\UploadImage;
use CultuurNet\UDB3\Media\Exceptions\InvalidFileSize;
use CultuurNet\UDB3\Media\Exceptions\InvalidFileType;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use League\Flysystem\FilesystemOperator;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class ImageUploaderService implements ImageUploaderInterface
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
        Description $description,
        CopyrightHolder $copyrightHolder,
        Language $language
    ): Uuid {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new InvalidFileType('The file did not upload correctly.');
        }

        $mimeType = $this->getFileMimeType($file);
        $this->guardMimeTypeSupported($mimeType);

        $this->guardFileSizeLimit($file);

        $fileId = new Uuid($this->uuidGenerator->generate());
        $fileName = $fileId->toString() . '.' . $this->guessExtensionForMimeType($mimeType);
        $destination = $this->getUploadDirectory() . '/' . $fileName;

        $file->getStream()->rewind();
        $this->filesystem->write($destination, $file->getStream()->getContents());

        $this->commandBus->dispatch(
            new UploadImage(
                $fileId,
                new MIMEType($mimeType),
                $description,
                $copyrightHolder,
                $destination,
                $language
            )
        );

        return $fileId;
    }

    public function uploadFromUrl(
        Url $url,
        Description $description,
        CopyrightHolder $copyrightHolder,
        Language $language
    ): Uuid {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'follow_location' => 1,
                'max_redirects' => 3,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $stream = @fopen($url->toString(), 'rb', false, $context);

        if ($stream === false) {
            throw new RuntimeException('Unable to open remote URL: ' . $url->toString());
        }

        $contents = '';
        $bytesRead = 0;

        while (!feof($stream)) {
            $chunk = fread($stream, 8192);

            if ($chunk === false) {
                fclose($stream);
                throw new RuntimeException('Error while reading remote file.');
            }

            $bytesRead += \strlen($chunk);

            if ($this->maxFileSize && $bytesRead > $this->maxFileSize) {
                fclose($stream);
                throw new InvalidFileSize(
                    'The remote file exceeds the maximum allowed size of ' . $this->maxFileSize . ' bytes.'
                );
            }

            $contents .= $chunk;
        }

        fclose($stream);

        if ($contents === '') {
            throw new RuntimeException('Downloaded file is empty.');
        }

        $mimeType = $this->getContentsMimeType($contents);
        $this->guardMimeTypeSupported($mimeType);

        $fileId = new Uuid($this->uuidGenerator->generate());
        $fileName = $fileId->toString() . '.' . $this->guessExtensionForMimeType($mimeType);
        $destination = $this->getUploadDirectory() . '/' . $fileName;

        $this->filesystem->write($destination, $contents);

        $this->commandBus->dispatch(
            new UploadImage(
                $fileId,
                new MIMEType($mimeType),
                $description,
                $copyrightHolder,
                $destination,
                $language
            )
        );

        return $fileId;
    }

    private function getFileMimeType(UploadedFileInterface $file): string
    {
        $finfo = new \finfo();
        $file->getStream()->rewind();

        /** @var string|false $mimeType */
        $mimeType = $finfo->buffer($file->getStream()->getContents(), FILEINFO_MIME_TYPE);
        return $mimeType !== false ? $mimeType : $file->getClientMediaType();
    }

    private function getContentsMimeType(string $contents): string
    {
        $finfo = new \finfo();

        /** @var string|false $mimeType */
        $mimeType = $finfo->buffer($contents, FILEINFO_MIME_TYPE);
        return $mimeType !== false ? $mimeType : '';
    }

    private function guardMimeTypeSupported(string $mimeType): void
    {
        $supportedMimeTypes = array_keys($this->supportedMimeTypes);
        if (!\in_array($mimeType, $supportedMimeTypes, true)) {
            throw new InvalidFileType(
                'The uploaded file has mime type "' . $mimeType . '" instead of ' . \implode(',', $supportedMimeTypes)
            );
        }
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
