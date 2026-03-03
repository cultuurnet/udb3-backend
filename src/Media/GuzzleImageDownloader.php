<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Media\Exceptions\InvalidFileSize;
use CultuurNet\UDB3\Media\Exceptions\InvalidFileType;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class GuzzleImageDownloader implements ImageDownloader
{
    private const TEST_HOST = 'io.uitdatabank.local';
    private ClientInterface $client;

    private int $maxFileSize;

    private array $supportedMimeTypes = [
        'image/png' => 'png',
        'image/jpeg' => 'jpeg',
        'image/gif' => 'gif',
    ];

    public function __construct(
        ClientInterface $client,
        int $maxFileSize = 10000
    ) {
        if ($maxFileSize < 0) {
            throw new RuntimeException('Max file size should be 0 or higher if not null.');
        }

        $this->client = $client;
        $this->maxFileSize = $maxFileSize;
    }

    public function download(Url $url): UploadedFileInterface
    {
        $this->validateUrl($url);
        $response = $this->client->sendRequest(
            new Request(
                'GET',
                $url->toString()
            )
        );

        if ($response->getStatusCode() !== 200) {
            throw new InvalidFileType('The file could not be downloaded correctly.');
        }

        $body = $response->getBody();
        $tempFile = tempnam(sys_get_temp_dir(), 'img_download_');
        $tempStream = fopen($tempFile, 'wb');
        $downloadedSize = 0;
        try {
            while (!$body->eof()) {
                $chunk = $body->read(8192);
                $downloadedSize += strlen($chunk);

                if ($downloadedSize > $this->maxFileSize) {
                    throw new InvalidFileSize(
                        'The file size of the uploaded image is too big. Max size (bytes):' . $this->maxFileSize
                    );
                }
                fwrite($tempStream, $chunk);
            }

            fclose($tempStream);
            $stream = Utils::streamFor(fopen($tempFile, 'rb'));

            $this->guardMimeTypeSupported($this->getFileMimeType($tempFile));

            return new UploadedFile(
                $stream,
                $downloadedSize,
                UPLOAD_ERR_OK,
                basename($tempFile),
                $response->getHeaderLine('Content-Type')
            );
        } finally {
            if (is_resource($tempStream)) {
                fclose($tempStream);
            }
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private function validateUrl(Url $url): void
    {
        $urlString = $url->toString();
        $parsedUrl = parse_url($urlString);

        // Only allow HTTP/HTTPS
        if (!in_array($parsedUrl['scheme'] ?? '', ['http', 'https'], true)) {
            throw new RuntimeException('Only HTTP and HTTPS schemes are allowed');
        }

        // Resolve hostname to IP and check if it's internal
        $host = $parsedUrl['host'] ?? '';
        if ($host === self::TEST_HOST) {
            return;
        }
        $ip = gethostbyname($host);

        if ($this->isInternalIp($ip)) {
            throw new RuntimeException('Access to internal resources is not allowed');
        }
    }

    private function isInternalIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    private function getFileMimeType(string $tempFile): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tempFile);
        finfo_close($finfo);
        return $mimeType;
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
}
