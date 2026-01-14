<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Media\Exceptions\InvalidFileSize;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class ImageDownloaderService implements ImageDownloaderInterface
{
    private Client $client;

    private int $maxFileSize;

    public function __construct(
        Client $client,
        int $maxFileSize = 0
    ) {
        if ($maxFileSize < 0) {
            throw new RuntimeException('Max file size should be 0 or higher if not null.');
        }

        $this->client = $client;
        $this->maxFileSize = $maxFileSize;
    }

    public function download(Url $url): UploadedFileInterface
    {
        $response = $this->client->get(
            $url->toString(),
            ['stream' => true]
        );

        $body = $response->getBody();
        $content = '';
        $downloadedSize = 0;

        while (!$body->eof()) {
            $chunk = $body->read(8192);
            $downloadedSize += strlen($chunk);

            if ($this->maxFileSize > 0 && $downloadedSize > $this->maxFileSize) {
                $body->close();
                throw new InvalidFileSize(
                    'The file size of the uploaded image is too big. Max size (bytes):' . $this->maxFileSize
                );
            }

            $content .= $chunk;
        }

        $stream = Utils::streamFor($content);

        return new UploadedFile(
            $stream,
            strlen($content),
            UPLOAD_ERR_OK,
            'temp',
            $response->getHeaderLine('Content-Type')
        );
    }
}
